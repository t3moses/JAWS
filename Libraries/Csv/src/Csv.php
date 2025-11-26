<?php 

    function csvToAssociativeArray($filename) {

    // Claude wrote this.

        $data = [];
        
        if (($handle = fopen($filename, 'r')) !== false) {
            // Read the header row
            $header = fgetcsv($handle, 0, ',','"', '\\');
            
            // Read each data row
            while (($row = fgetcsv($handle, 0, ',','"', '\\')) !== false) {
                // Combine header with row values
                $data[] = array_combine($header, $row);
            }
            
            fclose($handle);
        }
    
        return $data;
    }

    function associativeArrayToCsv($data, $filename) {

    // Claude wrote this.

        if (empty($data)) {
            return false;
        }
        
        $handle = fopen($filename, 'w');
        
        // Write header row from array keys
        fputcsv($handle, array_keys($data[0]), ',', '"', '\\');
        
        // Write data rows
        foreach ($data as $row) {
            fputcsv($handle, $row, ',', '"', '\\');
        }
        
        fclose($handle);
        return true;
    }
?>