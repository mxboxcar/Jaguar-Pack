<?php

namespace App\Domain\Reports;

use RuntimeException;
use ZipArchive;

/**
 * แทน PHPExcel (assets/phpexcel เดิม — ไม่รองรับ PHP 8) สำหรับรายงาน _admin/_report/refill_5/6/7.php, shipping.php
 * เขียนไฟล์ .xlsx (Excel2007) แผ่นเดียวด้วย ZipArchive: ความกว้างคอลัมน์, ตัวอักษรหัวตาราง, จัดชิด, รูปแบบตัวเลข "#,##0.00"
 *
 * ชนิดค่าในเซลล์ = PHPExcel_Cell_DefaultValueBinder เดิม: null = เซลล์ว่าง, string ที่เป็นตัวเลข (^-?\d+\.?\d*|\d*\.?\d+$) = ตัวเลข, อื่นๆ = ข้อความ
 * ต่างจากเดิมโดยตั้งใจ: ข้อความที่ขึ้นต้นด้วย "=" เดิมกลายเป็นสูตร (formula injection) → ที่นี่เป็นข้อความเสมอ
 * (ถ้าเจ้าของเพิ่ม phpoffice/phpspreadsheet ภายหลัง เปลี่ยนมาใช้แทนคลาสนี้ได้ — ไม่ต้องแก้ controller มาก)
 */
final class XlsxWriter
{
    /** style id ใน styles.xml (cellXfs) */
    public const DEFAULT = 0;

    public const HEAD = 1;      // Tahoma 10 ตัวหนา สีดำ จัดกลาง ($styleHead)

    public const CENTER = 2;    // $styleCenter

    public const RIGHT = 3;     // $styleRight

    public const RIGHT_NUM = 4; // $styleRight + "#,##0.00"

    /** @var array<string, float> */
    private array $widths = [];

    /** @var array<int, array<string, array{0: mixed, 1: int}>> */
    private array $cells = [];

    public function __construct(private string $title = 'Report') {}

    public function width(string $col, float $w): self
    {
        $this->widths[$col] = $w;

        return $this;
    }

    /** SetCellValue("A1", $v) + applyFromArray(style) */
    public function set(string $col, int $row, mixed $value, int $style = self::DEFAULT): self
    {
        $this->cells[$row][$col] = [$value, $style];

        return $this;
    }

    /** เปลี่ยน style ของเซลล์ที่มีอยู่ (getStyle()->applyFromArray()) — เซลล์ที่ยังไม่มีค่า = เซลล์ว่างที่มี style */
    public function style(string $col, int $row, int $style): self
    {
        $this->cells[$row][$col] = [$this->cells[$row][$col][0] ?? null, $style];

        return $this;
    }

    public function toString(): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        if ($tmp === false) {
            throw new RuntimeException('tempnam failed');
        }
        try {
            $zip = new ZipArchive;
            if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('zip open failed');
            }
            $zip->addFromString('[Content_Types].xml', $this->contentTypes());
            $zip->addFromString('_rels/.rels', $this->rootRels());
            $zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n"
                .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>Microsoft Excel</Application></Properties>');
            $zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n"
                .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:creator>Unknown Creator</dc:creator></cp:coreProperties>');
            $zip->addFromString('xl/workbook.xml', $this->workbook());
            $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
            $zip->addFromString('xl/styles.xml', $this->styles());
            $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheet());
            $zip->close();

            return (string) file_get_contents($tmp);
        } finally {
            @unlink($tmp);
        }
    }

    /** ชนิดค่าแบบ PHPExcel_Cell_DefaultValueBinder::dataTypeForValue() (1.8 ใน assets/phpexcel เดิม) */
    public static function isNumeric(mixed $v): bool
    {
        if (is_int($v) || is_float($v)) {
            return true;
        }

        return is_string($v) && preg_match('/^\-?([0-9]+\.?[0-9]*|[0-9]*\.?[0-9]+)$/', $v) === 1;
    }

    private static function x(string $s): string
    {
        // ตัดอักขระควบคุมที่ XML ไม่ยอม (เดิม PHPExcel ตัดเช่นกัน)
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s) ?? '';

        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function sheet(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n"
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        if ($this->widths !== []) {
            $cols = $this->widths;
            uksort($cols, fn ($a, $b) => self::colIndex($a) <=> self::colIndex($b));
            $xml .= '<cols>';
            foreach ($cols as $c => $w) {
                $i = self::colIndex($c);
                // PHPExcel เขียนความกว้าง + 0.71 (padding ของ Calibri 11)
                $xml .= '<col min="'.$i.'" max="'.$i.'" width="'.round($w + 0.7109375, 7).'" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }
        $xml .= '<sheetData>';
        $rows = $this->cells;
        ksort($rows);
        foreach ($rows as $r => $cols) {
            uksort($cols, fn ($a, $b) => self::colIndex($a) <=> self::colIndex($b));
            $xml .= '<row r="'.$r.'">';
            foreach ($cols as $c => [$v, $style]) {
                $ref = $c.$r;
                $s = $style !== self::DEFAULT ? ' s="'.$style.'"' : '';
                if ($v === null) {
                    $xml .= '<c r="'.$ref.'"'.$s.'/>';
                } elseif (self::isNumeric($v)) {
                    $xml .= '<c r="'.$ref.'"'.$s.'><v>'.self::x((string) (is_string($v) ? $v : Php74::str($v))).'</v></c>';
                } else {
                    $xml .= '<c r="'.$ref.'"'.$s.' t="inlineStr"><is><t xml:space="preserve">'.self::x((string) $v).'</t></is></c>';
                }
            }
            $xml .= '</row>';
        }

        return $xml.'</sheetData><pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/></worksheet>';
    }

    private static function colIndex(string $col): int
    {
        $n = 0;
        foreach (str_split(strtoupper($col)) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return $n;
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n"
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0.00"/></numFmts>'
            .'<fonts count="2">'
            .'<font><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="10"/><color rgb="FF000000"/><name val="Tahoma"/></font>'
            .'</fonts>'
            .'<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="5">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="right"/></xf>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right"/></xf>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    private function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n"
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.self::x($this->title).'" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n"
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n"
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>';
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n"
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .'</Types>';
    }
}
