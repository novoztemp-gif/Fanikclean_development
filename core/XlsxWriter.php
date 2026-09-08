<?php
/**
 * Minimal dependency-free .xlsx writer. An .xlsx file is just a ZIP archive of
 * a handful of XML parts, so this hand-rolls the required parts using PHP's
 * built-in ZipArchive rather than pulling in a full library (PhpSpreadsheet
 * etc. aren't installed and this app has no composer/vendor setup at all).
 * Supports: a title row (bold, merged across the sheet), a header row (bold,
 * shaded), and plain data rows of strings/numbers. That covers every export
 * this app needs.
 */
class XlsxWriter {
    /**
     * Streams a single-sheet .xlsx file to the browser and exits.
     *
     * @param string $filename   Download filename, e.g. "attendance-register-2026-09.xlsx"
     * @param string $sheetTitle Sheet tab name (max 31 chars, no special chars enforced here)
     * @param string $title      One title line shown bold above the table (empty to skip)
     * @param array  $headers    Column header labels
     * @param array  $rows       Array of rows; each row is an array of scalar cell values
     * @param array  $colWidths  Optional column widths (character units), same length as $headers
     */
    public static function download($filename, $sheetTitle, $title, array $headers, array $rows, array $colWidths = []) {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);

        $zip->addEmptyDir('_rels');
        $zip->addEmptyDir('xl');
        $zip->addEmptyDir('xl/_rels');
        $zip->addEmptyDir('xl/worksheets');
        $zip->addEmptyDir('docProps');

        $zip->addFromString('[Content_Types].xml', self::contentTypes());
        $zip->addFromString('_rels/.rels', self::rootRels());
        $zip->addFromString('docProps/core.xml', self::coreProps());
        $zip->addFromString('docProps/app.xml', self::appProps($sheetTitle));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels());
        $zip->addFromString('xl/workbook.xml', self::workbook($sheetTitle));
        $zip->addFromString('xl/styles.xml', self::styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheet($title, $headers, $rows, $colWidths));

        $zip->close();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    private static function colLetter($idx) {
        $letter = '';
        $idx++;
        while ($idx > 0) {
            $mod = ($idx - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $idx = (int)(($idx - $mod) / 26);
        }
        return $letter;
    }

    private static function esc($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private static function cell($colIdx, $rowNum, $value, $styleId = 0) {
        $ref = self::colLetter($colIdx) . $rowNum;
        $s = $styleId ? ' s="' . $styleId . '"' : '';
        if (is_numeric($value) && $value !== '') {
            return "<c r=\"$ref\"$s><v>" . self::esc($value) . "</v></c>";
        }
        if ($value === '' || $value === null) {
            return "<c r=\"$ref\"$s/>";
        }
        return "<c r=\"$ref\"$s t=\"inlineStr\"><is><t xml:space=\"preserve\">" . self::esc($value) . "</t></is></c>";
    }

    private static function sheet($title, array $headers, array $rows, array $colWidths) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        if (!empty($colWidths)) {
            $xml .= '<cols>';
            foreach ($colWidths as $i => $w) {
                $xml .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';
        $rowNum = 1;

        if ($title !== '') {
            $xml .= "<row r=\"$rowNum\">" . self::cell(0, $rowNum, $title, 2) . '</row>';
            $rowNum++;
            $rowNum++; // blank spacer row
        }

        $headerRow = $rowNum;
        $xml .= "<row r=\"$headerRow\">";
        foreach ($headers as $i => $h) {
            $xml .= self::cell($i, $headerRow, $h, 1);
        }
        $xml .= '</row>';
        $rowNum++;

        foreach ($rows as $r) {
            $xml .= "<row r=\"$rowNum\">";
            foreach (array_values($r) as $i => $v) {
                $xml .= self::cell($i, $rowNum, $v);
            }
            $xml .= '</row>';
            $rowNum++;
        }

        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    private static function styles() {
        // s=0 default, s=1 bold header w/ shaded fill + border, s=2 bold title
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="3">
    <font><sz val="10"/><name val="Calibri"/></font>
    <font><b/><sz val="10"/><color rgb="FF161A1F"/><name val="Calibri"/></font>
    <font><b/><sz val="13"/><color rgb="FF161A1F"/><name val="Calibri"/></font>
  </fonts>
  <fills count="3">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFF3F4F5"/><bgColor indexed="64"/></patternFill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border><left style="thin"><color rgb="FFD6D9DC"/></left><right style="thin"><color rgb="FFD6D9DC"/></right><top style="thin"><color rgb="FFD6D9DC"/></top><bottom style="thin"><color rgb="FFD6D9DC"/></bottom><diagonal/></border>
  </borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="3">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
    <xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>
  </cellXfs>
  <cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>';
    }

    private static function contentTypes() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>';
    }

    private static function rootRels() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>';
    }

    private static function workbookRels() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';
    }

    private static function workbook($sheetTitle) {
        $name = self::esc(substr($sheetTitle, 0, 31));
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets><sheet name="' . $name . '" sheetId="1" r:id="rId1"/></sheets>
</workbook>';
    }

    private static function coreProps() {
        $now = gmdate('Y-m-d\TH:i:s\Z');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:creator>FanikClean</dc:creator>
  <dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created>
</cp:coreProperties>';
    }

    private static function appProps($sheetTitle) {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">
  <Application>FanikClean</Application>
  <TitlesOfParts><vt:vector xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes" size="1" baseType="lpstr"><vt:lpstr>' . self::esc($sheetTitle) . '</vt:lpstr></vt:vector></TitlesOfParts>
</Properties>';
    }
}
