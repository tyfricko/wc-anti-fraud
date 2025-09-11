<?php

if (!defined('ABSPATH')) {
    exit();
}

if (!class_exists('WCAF_Debug_Log')) {
    class WCAF_Debug_Log {
        public $log_contents = null;
        public $log_path = null;
        public $log_file_name = null;

        /**
         * WCAF_Log constructor.
         *
         * @param null $path
         * @param null $file_name
         */
        public function __construct($path = null, $file_name = null) {
            $this->log_contents = '';
            $this->log_path = WCAF_LOG_DIR;
            if ($path !== null) {
                $this->log_path = $path;
            }
            $this->log_file_name = date("Y-m-d") . '-wcaf'  . '.txt';

            if ($file_name) {
                $this->log_file_name = $file_name;
            }
        }

        public function write($log_text = '') {
            ob_start();
            print_r($log_text);
            $this->log_contents .= ob_get_clean() . PHP_EOL;
        }

        public function get_log_full_path() {
            return $this->log_path . $this->log_file_name;
        }

        public function save() {
            file_put_contents($this->get_log_full_path(), $this->log_contents, FILE_APPEND);
        }

    }
}

