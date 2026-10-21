#!/usr/bin/env python3
# สร้าง docs/pack-diff.md: ไฟล์เดิมของ pack ต่อกลุ่ม เทียบกับโค้ดเดิมของ SR (สิ่งที่ app นี้พอร์ตไว้แล้ว)
# ใช้: python3 -I tools/pack-diff.py > docs/pack-diff.md   (อ่านเว็บจริงของ pack/SR/jl แบบอ่านอย่างเดียว + docs/url-map.md)
# `jl` = ไฟล์เดิมของ pack เหมือน jl มากกว่า SR (ใช้โค้ด Laravel ของ jl เป็นแม่แบบได้)
import re, subprocess, os
PK = '/home/admin/web/portal.pack-cargo.net/public_html'
SR = '/home/admin/web/portal.srshippingsystem.com/public_html'
JL = '/home/admin/web/portal.jlcargoth.com/public_html'
here = os.path.dirname(os.path.abspath(__file__))
groups, cur = {}, None
for line in open(os.path.join(here, '..', 'docs', 'url-map.md'), encoding='utf-8'):
    m = re.match(r'^### (\S+) \(\d+ ไฟล์\)', line)
    if m: cur = m.group(1); continue
    if line.startswith('## '): cur = None
    m = re.match(r'^\| `([^`]+\.php)` \|', line)
    if cur and m: groups.setdefault(cur, []).append(m.group(1))
def ndiff(base, f):
    a, b = os.path.join(base, f), os.path.join(PK, f)
    if not os.path.exists(a) or not os.path.exists(b): return None
    r = subprocess.run(['diff', '-wB', a, b], capture_output=True)
    if r.returncode == 0: return 0
    return sum(1 for l in r.stdout.decode('utf-8', 'replace').splitlines() if l[:1] in '<>')
def pkfiles():
    out = []
    for d, ds, fs in os.walk(PK):
        ds[:] = [x for x in ds if x not in ('vendor', 'upload', 'uploads', 'logs', 'assets', 'lib', 'sessionmaster')]
        out += [os.path.relpath(os.path.join(d, n), PK) for n in fs if n.endswith('.php')]
    return set(out)
allpk = pkfiles()
mapped = {f for fs in groups.values() for f in fs}
out = ['# pack เทียบ SR: ไฟล์เดิมต่อกลุ่ม', '',
       'สร้างด้วย `python3 -I tools/pack-diff.py > docs/pack-diff.md` (เว็บจริงของ pack / SR / jl, `diff -wB`)',
       '`same` = เหมือน SR → ใช้โค้ดที่พอร์ตแล้วได้เลย · `diff N (jl M)` = ต่าง SR N บรรทัด, ต่าง jl M บรรทัด (jl น้อยกว่า = ใช้ jl เป็นแม่แบบ) ·',
       '`SR-only` = pack ไม่มีไฟล์นี้ → ลบ route · `pack-only` = ต้องพอร์ตใหม่', '']
summary = []
for g in ['tracking','shop','order','payment','balance','money-move','points','lot','warehouse','users','messaging','reports','settings','foundation']:
    files = sorted(groups.get(g, []))
    rows, cnt = [], {'same': 0, 'diff': 0, 'SR-only': 0}
    for f in files:
        if f not in allpk: rows.append(f'| `{f}` | SR-only |'); cnt['SR-only'] += 1; continue
        d = ndiff(SR, f)
        if d == 0: st = 'same'; cnt['same'] += 1
        else:
            j = ndiff(JL, f)
            st = f'diff {d}' + (f' (jl {j})' if j is not None else ''); cnt['diff'] += 1
        rows.append(f'| `{f}` | {st} |')
    summary.append(f"| {g} | {cnt['same']} | {cnt['diff']} | {cnt['SR-only']} |")
    out += [f'## {g}', '', '| ไฟล์ | สถานะ |', '|---|---|'] + rows + ['']
extra = sorted(f for f in allpk - mapped if not os.path.exists(os.path.join(SR, f)))
out += ['## pack-only (ไม่มีใน SR)', '', '| ไฟล์ | jl มีไหม |', '|---|---|'] + [f"| `{f}` | {'มี' if os.path.exists(os.path.join(JL, f)) else '-'} |" for f in extra] + ['']
out[6:6] = ['| กลุ่ม | same | diff | SR-only |', '|---|---:|---:|---:|'] + summary + ['']
def cols(db):
    r = subprocess.run(['mysql', '-N', '-e', f"select table_name, column_name, column_type from information_schema.columns where table_schema='{db}' order by 1,2"], capture_output=True, text=True)
    return {tuple(l.split('\t')[:2]): l.split('\t')[2] for l in r.stdout.splitlines() if l}
S, P = cols('admin_sr_bench'), cols('admin_pack_bench')
stt, pt = {t for t, _ in S}, {t for t, _ in P}
out += ['## schema: admin_sr_bench → admin_pack_bench', '', f"- ตารางที่ SR มีแต่ pack ไม่มี: {', '.join(f'`{t}`' for t in sorted(stt - pt)) or '-'}",
        f"- ตารางที่ pack มีแต่ SR ไม่มี: {', '.join(f'`{t}`' for t in sorted(pt - stt)) or '-'}", '', '| ตาราง | คอลัมน์ | SR | pack |', '|---|---|---|---|']
for k in sorted(set(S) | set(P)):
    if k[0] not in stt or k[0] not in pt: continue
    a, b = S.get(k), P.get(k)
    if a != b: out.append(f'| `{k[0]}` | `{k[1]}` | {a or "**ไม่มี**"} | {b or "**ไม่มี**"} |')
out.append('')
# ส่วนเขียนมือ (เช่น "## foundation helpers") อยู่หลัง MARK — คงไว้ตอนสร้างใหม่
# (ถ้า redirect ทับไฟล์เดิม ไฟล์ถูกล้างก่อนอ่าน → ใช้ของ git HEAD แทน)
MARK = '<!-- เขียนมือ: tools/pack-diff.py คงส่วนนี้ไว้ตอนสร้างใหม่ -->'
doc = os.path.join(here, '..', 'docs', 'pack-diff.md')
old = open(doc, encoding='utf-8').read() if os.path.exists(doc) else ''
if MARK not in old:
    old = subprocess.run(['git', '-C', os.path.join(here, '..'), 'show', 'HEAD:docs/pack-diff.md'], capture_output=True, text=True).stdout
if MARK in old:
    out.append(old[old.index(MARK):].rstrip('\n'))
print('\n'.join(out))
