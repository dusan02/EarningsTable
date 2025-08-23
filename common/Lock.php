<?php
/**
 * File-based Lock Mechanism
 * Prevents multiple cron processes from running simultaneously
 */

final class Lock {
    private string $file;
    private $handle;

    public function __construct(string $name) {
        $this->file = sys_get_temp_dir() . "/{$name}.lock";
    }

    public function acquire(int $timeout = 10): bool {
        $this->handle = fopen($this->file, 'c');
        $start = time();
        
        do {
            if (flock($this->handle, LOCK_EX | LOCK_NB)) {
                return true;
            }
            usleep(200_000); // 0.2 s
        } while (time() - $start < $timeout);
        
        return false;
    }

    public function release(): void {
        if ($this->handle) {
            flock($this->handle, LOCK_UN);
            fclose($this->handle);
            @unlink($this->file);
        }
    }
}
?> 