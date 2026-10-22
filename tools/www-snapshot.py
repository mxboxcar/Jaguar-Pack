#!/usr/bin/env python3
# สร้าง resources/www/ จากเว็บ WordPress เดิมของ pack-cargo.net (WP 5.2 + Divi, PHP 7.4) — ใช้ตอนย้าย www มาให้ Laravel เสิร์ฟ
# ใช้: python3 -I tools/www-snapshot.py   (GET อย่างเดียว ผ่าน nginx ในเครื่อง + อ่าน DB admin_packwp2 แบบอ่านอย่างเดียว)
# หลังสลับ www แล้ว WordPress ไม่ทำงาน — สคริปต์นี้ใช้ได้เฉพาะก่อนสลับ (หรือหลังเปิด WordPress กลับชั่วคราว)
import base64, json, os, re, subprocess, urllib.parse

HOST = 'pack-cargo.net'
DB = 'admin_packwp2'
MARK = 'wwwsearchmarker'
here = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.join(here, '..', 'resources', 'www')


def get(path):
    r = subprocess.run(['curl', '-sk', '--resolve', f'{HOST}:443:127.0.0.1', '-o', '-', '-w', '\n%{http_code}',
                        f'https://{HOST}{path}'], capture_output=True, check=True)
    body, _, code = r.stdout.rpartition(b'\n')
    return int(code), body.decode('utf-8')


def self_marker(xml):
    # <atom:link rel="self"> = URL ที่ขอมาจริง (self_link() ของ WordPress อ่าน REQUEST_URI) → controller เติมตอนตอบ
    new, n = re.subn(r'<atom:link href="[^"]*" rel="self"', '<atom:link href="%%SELF%%" rel="self"', xml)
    assert n == 1
    return new


def save(name, text):
    p = os.path.join(OUT, name)
    os.makedirs(os.path.dirname(p), exist_ok=True)
    with open(p, 'w', encoding='utf-8', newline='') as f:
        f.write(text)


def rows(sql):
    r = subprocess.run(['mysql', '-N', '--raw', '-B', DB, '-e', sql], capture_output=True, text=True, check=True)
    return [l.split('\t') for l in r.stdout.split('\n') if l]


# ----- หน้า/โพสต์ที่เผยแพร่ (ที่ WordPress ค้นหาได้: page + post สถานะ publish ไม่มีรหัสผ่าน)
front = rows("select option_value from wp_options where option_name='page_on_front'")[0][0]
items = []
q = ("select ID, post_type, post_name, date_format(post_date,'%Y/%m/%d'), post_date, "
     "replace(to_base64(post_title),'\n',''), replace(to_base64(post_excerpt),'\n',''), replace(to_base64(post_content),'\n','') from wp_posts "
     "where post_status='publish' and post_type in ('page','post') and post_password='' order by ID")
b64 = lambda s: base64.b64decode(s).decode('utf-8')
for pid, typ, name, ymd, date, t, e, c in rows(q):
    name = urllib.parse.unquote(name)
    path = '/' if pid == front else (f'/{ymd}/{name}/' if typ == 'post' else f'/{name}/')
    items.append(dict(id=int(pid), type=typ, name=name, path=path, date=date,
                      title=b64(t), excerpt=b64(e), content=b64(c)))

# ----- HTML ของแต่ละหน้า (เหมือนเดิมทุกไบต์ — ตรวจแล้วว่าไม่เปลี่ยนตามคำขอ)
for it in items:
    code, html = get(urllib.parse.quote(it['path']))
    assert code == 200, (it['path'], code)
    save(f"pages/{it['id']}.html", html)
    # Divi: reCAPTCHA action = basename(permalink) แทนอักขระที่ไม่ใช่ A-Za-z0-9 ด้วย _ (ใช้ในหน้าค้นหา = ผลลัพธ์แรก)
    perma = f'https://{HOST}' + ('/' if it['path'] == '/' else urllib.parse.quote(it['path']).lower())
    it['action'] = re.sub(r'[^A-Za-z0-9]', '_', os.path.basename(perma.rstrip('/')))
    # body class et_pb_pagebuilder_layout = หน้านี้ทำด้วย Divi builder (หน้าค้นหาใส่ class นี้ตาม $post ตัวแรก)
    it['builder'] = bool(re.search(r'<body class="[^"]*\bet_pb_pagebuilder_layout\b', html))
    m = re.search(r'"page_action":\{"action":"([^"]*)"\}', html)
    assert m and m.group(1) == it['action'], (it['path'], m and m.group(1), it['action'])

# ----- 404 (WordPress ตอบหน้าเดียวกันทุก URL)
code, html = get('/www-snapshot-missing/')
assert code == 404
# ช่องค้นหา 2 ช่องแสดงคำค้น (WordPress ตอบ 404 นี้ตอนขอหน้าผลค้นหาเกินจำนวนหน้า เช่น /page/9/?s=x)
for a in ['placeholder="ค้นหา &hellip;" value="" name="s"', '<input type="text" value="" name="s" id="s" />']:
    assert html.count(a) == 1, a
    html = html.replace(a, a.replace('value=""', 'value="%%TERM_HTML%%"'))
save('404.html', html)

# ----- บล็อกผลค้นหาต่อรายการ (ไม่ขึ้นกับคำค้น): ค้น "e" ได้ครบทุกหน้า (5 ต่อหน้า)
arts = {}
for n in range(1, 10):
    code, html = get('/?s=e' if n == 1 else f'/page/{n}/?s=e')
    if code != 200:
        break
    for block, pid in re.findall(r'(<article id="post-(\d+)".*?</article> <!-- \.et_pb_post -->)', html, re.S):
        arts[int(pid)] = block
assert sorted(arts) == sorted(i['id'] for i in items), (sorted(arts), [i['id'] for i in items])
for pid, block in arts.items():
    save(f'articles/{pid}.html', block)

# ----- หน้าอื่นที่ยังมีลิงก์ชี้มา: หมวดหมู่/ผู้เขียน (ในบล็อกผลค้นหา) และฟีดความเห็นของแต่ละโพสต์ (<link> ใน head)
extra, seen = {}, set()
srcs = [open(os.path.join(OUT, 'pages', f'{i["id"]}.html'), encoding='utf-8').read() for i in items] + list(arts.values())
for src in srcs:
    for u in re.findall(r'href="https://pack-cargo\.net(/(?:category|author)/[^"]+/|/20\d\d/\d\d/\d\d/[^"/]+/feed/)"', src):
        path = urllib.parse.unquote(u)
        if path in seen:
            continue
        seen.add(path)
        code, body = get(u)
        assert code == 200, (path, code)
        name = f'extra/{len(extra) + 1}.' + ('xml' if path.endswith('/feed/') else 'html')
        save(name, self_marker(body) if name.endswith('.xml') else body)
        extra[path] = [name, 'application/rss+xml; charset=UTF-8' if path.endswith('/feed/') else 'text/html; charset=UTF-8']

# ----- แม่แบบหน้าค้นหา: ค้นคำที่ไม่มีผลลัพธ์ แล้วแทนจุดที่ขึ้นกับคำค้นด้วย %%...%%
code, html = get(f'/?s={MARK}')
assert code == 200
i = html.index('<div id="left-area">') + len('<div id="left-area">')
j = html.index('</div> <!-- #left-area -->')
save('search-empty.html', html[i:j])
html = html[:i] + '%%LEFT%%' + html[j:]
subs = [
    (f'<title>ค้นหาผลลัพธ์สำหรับ &quot;{MARK}&quot; | Pack Cargo</title>', '<title>ค้นหาผลลัพธ์สำหรับ &quot;%%TERM_HTML%%&quot; | Pack Cargo</title>'),
    (f'&#8220;{MARK}&#8221; ฟีด" href="https://{HOST}/search/{MARK}/feed/rss2/"', '&#8220;%%TERM_HTML%%&#8221; ฟีด" href="https://pack-cargo.net/search/%%TERM_FEED%%feed/rss2/"'),
    (f"'send','pageview','/?s=no-results:{MARK}&cat=no-results'", "'send','pageview','/?s=%%GA%%'"),
    (f'"siteSearchTerm":"{MARK}","siteSearchFrom":"","siteSearchResults":0', '"siteSearchTerm":%%TERM_JSON%%,"siteSearchFrom":%%FROM_JSON%%,"siteSearchResults":%%COUNT%%'),
    ('"page_action":{"action":""}', '"page_action":{"action":"%%ACTION%%"}'),
    ('"page_id":""', '"page_id":"%%PAGE_ID%%"'),
    ('"ab_bounce_rate":""', '"ab_bounce_rate":"%%BOUNCE%%"'),
    (' et_pb_gutters3 et_right_sidebar ', ' et_pb_gutters3%%BUILDER%% et_right_sidebar '),
    ('<body class="search search-no-results ', '<body class="search %%BODY%% '),
    (f'value="{MARK}"', 'value="%%TERM_HTML%%"'),
]
for a, b in subs:
    n = html.count(a)
    assert n >= 1, a
    html = html.replace(a, b)
assert MARK not in html
save('search.html', html)

# ----- feed / robots (ข้อความคงที่)
for path, name in [('/feed/', 'feed.xml'), ('/comments/feed/', 'comments-feed.xml'), ('/robots.txt', 'robots.txt')]:
    code, body = get(path)
    assert code == 200, path
    save(name, self_marker(body) if name.endswith('.xml') else body)

for it in items:
    del it['name']
json.dump(dict(front=int(front), items=items, extra=extra), open(os.path.join(OUT, 'site.json'), 'w', encoding='utf-8'),
          ensure_ascii=False, indent=1)
print(f'ok: {len(items)} pages, {len(arts)} search blocks, {len(extra)} extra → resources/www/')
