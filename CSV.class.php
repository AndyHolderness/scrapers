<?php

class CSV
{
    public static function readCSVintoArray($file)
    {
        $outPutArray = [];

        $file_name = $file;
        if (($handle = fopen($file_name, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
                $outPutArray[] = $data;
            }
            fclose($handle);
        }

        return $outPutArray;
    }

    public static function writeArrayintoCSV($filecontents, $file)
    {
        $fp = fopen($file, 'w');

        //$infos array containing comma separated values
        foreach ($filecontents as $line) {
            fputcsv($fp, $line);
        }

        fclose($fp);
    }

}