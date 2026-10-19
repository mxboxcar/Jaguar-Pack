#!/usr/bin/env python3
# สร้าง docs/sr-diff.md: ไฟล์เดิมของ SR ต่อกลุ่ม เทียบกับโค้ดเดิมของ jl (สิ่งที่ app jl พอร์ตไว้แล้ว)
# ใช้: python3 -I tools/sr-diff.py > docs/sr-diff.md   (อ่าน /home/admin/bench/{sr-old,jl-old} + docs/url-map.md)
import re, subprocess, os, sys
SR, JL = '/home/admin/bench/sr-prehotfix', '/home/admin/bench/jl-old'  # sr-prehotfix = SR ก่อน hotfix 2026-10-18 (เทียบกับ jl-old ซึ่งก่อน hotfix เหมือนกัน)
here = os.path.dirname(os.path.abspath(__file__))
groups, cur = {}, None
for line in open(os.path.join(here, '..', 'docs', 'url-map.md'), encoding='utf-8'):
    m = re.match(r'^### (\S+) \(\d+ ไฟล์\)', line)
    if m: cur = m.group(1); continue
    if line.startswith('## '): cur = None
    m = re.match(r'^\| `([^`]+\.php)` \|', line)
    if cur and m: groups.setdefault(cur, []).append(m.group(1))
def norm(p):
    s = open(p, 'rb').read().decode('utf-8', 'replace')
    return re.sub(r'jlcargoth|srshippingsystem', 'X', s, flags=re.I)
def ndiff(f):
    a, b = os.path.join(JL, f), os.path.join(SR, f)
    if not os.path.exists(a): return None
    r = subprocess.run(['diff', '-wB', a, b], capture_output=True)
    if r.returncode == 0: return 0
    return sum(1 for l in r.stdout.decode('utf-8', 'replace').splitlines() if l[:1] in '<>')
out = ['# SR เทียบ jl: ไฟล์เดิมต่อกลุ่ม', '', 'สร้างด้วย `python3 -I tools/sr-diff.py > docs/sr-diff.md` — `same` = ไฟล์เดิมของ SR เหมือน jl (ไม่นับช่องว่าง) → route/controller ของ jl ใช้ได้เลย (ยังต้องเทียบผลจริงเพราะ schema/helper ต่าง),',
       '`diff N` = ต่าง N บรรทัด → ต้องปรับตาม SR, `SR-only` = ต้องพอร์ตใหม่, `JL-only` = มีใน app ที่ fork มาแต่ SR ไม่มี → ลบ route หรือคงไว้ตามที่ SR เดิมทำ', '']
summary = []
for g in ['tracking','shop','order','payment','balance','money-move','points','lot','warehouse','users','messaging','reports','settings','foundation']:
    files = sorted(groups.get(g, []))
    rows, cnt = [], {'same':0,'diff':0,'SR-only':0}
    for f in files:
        d = ndiff(f)
        if d is None: st = 'SR-only'; cnt['SR-only'] += 1
        elif d == 0: st = 'same'; cnt['same'] += 1
        else: st = f'diff {d}'; cnt['diff'] += 1
        rows.append(f'| `{f}` | {st} |')
    # jl-only: files in jl-old under the same dirs that SR lacks
    jlonly = []
    dirs = sorted({os.path.dirname(f) for f in files if os.path.dirname(f)})
    for d in dirs:
        jd = os.path.join(JL, d)
        if os.path.isdir(jd):
            for n in sorted(os.listdir(jd)):
                rel = f'{d}/{n}'
                if n.endswith('.php') and not os.path.exists(os.path.join(SR, rel)): jlonly.append(rel)
    for f in jlonly: rows.append(f'| `{f}` | JL-only |')
    summary.append(f"| {g} | {cnt['same']} | {cnt['diff']} | {cnt['SR-only']} | {len(jlonly)} |")
    out += [f'## {g}', '', '| ไฟล์ | สถานะ |', '|---|---|'] + rows + ['']
out[5:5] = ['| กลุ่ม | same | diff | SR-only | JL-only |', '|---|---:|---:|---:|---:|'] + summary + ['']
print('\n'.join(out))
