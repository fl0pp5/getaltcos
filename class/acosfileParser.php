 <?php

 class acosfileParser {

    function __construct($file) {
      if (!file_exists($file)) {
        $this->error = "Файл $file отсутствует";
        return;
      }
      $fp = fopen($file, 'r');
      while (strlen(trim($str=fread($fp))) == 0) ;
      if (rtrim(substr($str, 0, 5)) != 'FROM') {
        $this->error = "Первый оператор отличается от 'FROM'";
        return;
      }
      $path = explode(' ', $str);
      $this->from = trim($path[1]);

    }

}
