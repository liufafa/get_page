
    public function get_pages($file_path = "") {
        if (!is_file($file_path)) {
            return 0;
        }

        $file_type = $this->get_file_type($file_path);

        switch ($file_type) {
            case "docx":
                return $this->_get_num_pages_docx($file_path);
                break;
            case "pdf":
                return $this->PageCount_PDF($file_path);
                break;

            default:
                return 1;
        }
        return 0;
    }
 /**
     * 获取word2007页数
     * @param type $filename
     * @return boolean
     */
    private function _get_num_pages_docx($filename) {
        $zip = new ZipArchive();
        if ($zip->open($filename) === true) {
            if (($index = $zip->locateName('docProps/app.xml')) !== false) {
                $data = $zip->getFromIndex($index);
                $zip->close();
                $xml = new SimpleXMLElement($data);
                if (!strstr($xml->Application, 'Microsoft Office')) {
                    return -1;
                }

                return $xml->Pages;
            }
            $zip->close();
        }
        return false;
    }

    private function PageCount_PDF($file) {
        $pageCount = 0;
        try {
            if (file_exists($file)) {
                require_once(APPPATH . 'third_party/fpdf/fpdf.php');
                require_once(APPPATH . 'third_party/fpdi/fpdi.php');
                $pdf = new FPDI();                              // initiate FPDI
                $pageCount = $pdf->setSourceFile($file);        // get the page count
                return $pageCount;
            }
        } catch (Exception $exc) {
            return $this->_get_file_pdf_pages($file);
        }
    }

    private function _get_file_pdf_pages($pdf_file = "") {
        $number = 0;
        $pages = array();
        try {
            require_once APPPATH . 'third_party/pdfparser/vendor/autoload.php';
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($pdf_file);
            $pages = $pdf->getPages();
            foreach ($pages as $page) {
                $number++;
            }
            return $number;
        } catch (Exception $e) {
            return $this->getPageTotal($pdf_file);
        }
    }

    public function getPageTotal($path) {
        // 打开文件
        try {
            if (!$fp = @fopen($path, "r")) {
                $error = "打开文件{$path}失败";
                return false;
            } else {
                $max = 0;
                while (!feof($fp)) {
                    $line = fgets($fp, 255);
                    if (preg_match('/\/Count [0-9]+/', $line, $matches)) {
                        preg_match('/[0-9]+/', $matches[0], $matches2);
                        if ($max < $matches2[0])
                            $max = $matches2[0];
                    }
                }
                fclose($fp);
                // 返回页数
                return $max;
            }
        } catch (Exception $exc) {
            return false;
        }
    }
