<?php
/**
 * 🗜️ COMPRESSION HANDLER
 * Kompresia a dekompresia záloh
 */

class CompressionHandler {
    
    /**
     * Kompresia súboru
     */
    public function compressFile($inputFile, $outputFile) {
        echo "🗜️ Compressing backup...\n";
        
        $input = gzopen($inputFile, 'rb');
        $output = fopen($outputFile, 'wb');
        
        if (!$input || !$output) {
            throw new Exception("Failed to open files for compression");
        }
        
        while (!gzeof($input)) {
            fwrite($output, gzread($input, 8192));
        }
        
        gzclose($input);
        fclose($output);
        
        echo "✅ Compression completed: " . number_format(filesize($outputFile)) . " bytes\n";
    }
    
    /**
     * Dekompresia súboru
     */
    public function decompressFile($inputFile, $outputFile) {
        $input = gzopen($inputFile, 'rb');
        $output = fopen($outputFile, 'wb');
        
        while (!gzeof($input)) {
            fwrite($output, gzread($input, 8192));
        }
        
        gzclose($input);
        fclose($output);
    }
}
?>
