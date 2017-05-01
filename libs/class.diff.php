<?php

class Diff {
/*
 * TypeA v1.5
 * Diff:
 * 1. Определяет различия в двух страницах
 * 2. Строит шаблон страницы, шаблон представляет собой регулярное выражение, по которому можно определить различия в страницах
 * 3. Определяет общий для двух страниц текст
 * 
 * Алгоритм работы:
 * 1. Определение наибольшей общей строки
 * 2. Построение шаблона
 * 2.1. За основу шаблона берется наибольшая общая строка
 * 2.2. Осуществляется поиск "вправо" от наибольшей общей строки (рекурсивный поиск общих строк, длина которых больше minLengthToSave символов)
 * 2.3. Осуществляется поиск "влево" от наибольшей общей строки (рекурсивный поиск общих строк, длина которых больше minLengthToSave символов)
 * 3. Определение различий в двух страницах
 */

// Настройки    
protected $minLengthToSave = 3;

// Начальные данные
protected $text1 = "";
protected $text1Length = 0;
protected $text2 = "";
protected $text2Length = 0;
// Наибольшая общая подстрока
protected $largestCommonSubstring = "";

// Статус BAD/OK
protected $status = "";
// Шаблон
protected $template = "";
// Различия
protected $different = array();
// Страница, содержащая только общие строки
protected $commonText = "";

public function __construct($text1, $text2) {
    if(!empty($text1) && !empty($text2)) {
        if(strlen($text2) > strlen($text1)) {
            $this->text1 = $text2;
            $this->text2 = $text1;
        } else {
            $this->text1 = $text1;
            $this->text2 = $text2;
        }

        $this->text1Length = strlen($this->text1);
        $this->text2Length = strlen($this->text2);
    }

    // Определение наибольшей общей подстроки
    $this->getLargestCommonSubstring();
    if(empty($this->largestCommonSubstring)) {
        $this->status = "BAD";
        return;
    }
//var_dump($this->largestCommonSubstring); die();
    // Построение шаблона
    $this->makeTemplate();
    // Получение массива с различиями
    $this->getDifferent();

    $this->status = "OK";
}

// Определение наибольшей общей подстроки
protected function getLargestCommonSubstring() {
    $maxCommonSubstring = "";
    $commonSubstring = "";

    for($i = 0; $i < $this->text1Length; $i++) {
        $x = $i;
        for($j = 0; $j < $this->text2Length; $j++) {
            for($y = $j; $y < $this->text2Length; $y++) {
                if($x >= $this->text1Length) break;
                if($this->text1[$x] == $this->text2[$y]) {
                    $commonSubstring .= $this->text1[$x];
                    $x++;
                } else {
                    if(!empty($commonSubstring)) break;
                }
            }
            if(strlen($commonSubstring) > strlen($maxCommonSubstring)) {
                $maxCommonSubstring = $commonSubstring;
            }
            $x = $i;
            $commonSubstring = "";
            if(strlen($maxCommonSubstring) >= ($this->text2Length - $j - 1)) break;
        }
        if(strlen($maxCommonSubstring) >= ($this->text1Length - $i - 1)) break;
    }
    
    if(strlen($maxCommonSubstring) < $this->minLengthToSave) {
        $maxCommonSubstring = "";
    }

    $this->largestCommonSubstring = $maxCommonSubstring;
}

// Построение шаблона
protected function makeTemplate() {
    $this->template = preg_quote($this->largestCommonSubstring, "#");
    $this->commonText = $this->largestCommonSubstring;
    $this->makeRightPartOfTemplate($this->text1, $this->text2, $this->largestCommonSubstring);
    $this->makeLeftPartOfTemplate($this->text1, $this->text2, $this->largestCommonSubstring);
    $this->template = "#{$this->template}#is";
}

// Рекурсивное построение правой части шаблона
protected function makeRightPartOfTemplate($text1, $text2, $commonSubstring) {
    if(empty($commonSubstring)) {
        return;
    }
    
    $text1Right = substr($text1, strpos($text1, $commonSubstring) + strlen($commonSubstring));
    $text1RightLength = strlen($text1Right);
    $text2Right = substr($text2, strpos($text2, $commonSubstring) + strlen($commonSubstring));
    $text2RightLength = strlen($text2Right);

    if(($text1RightLength <= $this->minLengthToSave) || ($text2RightLength <= $this->minLengthToSave)) {
        return;
    }

    $commonSubstring = "";
    $stop = false;
    for($i = 0; $i < $text1RightLength; $i++) {
        $x = $i;
        for($j = 0; $j < $text2RightLength; $j++) {
            for($y = $j; $y < $text2RightLength; $y++) {
                if($x >= $text1RightLength) break;
                if($text1Right[$x] == $text2Right[$y]) {
                    $commonSubstring .= $text1Right[$x];
                    $x++;
                } else {
                    if(!empty($commonSubstring)) break;
                }
            }
            if(strlen($commonSubstring) >= $this->minLengthToSave) {
                $stop = true;
            }
            $x = $i;
            if($stop) break;
            $commonSubstring = "";
        }
        if($stop) break;
    }

    if(strlen($commonSubstring) < $this->minLengthToSave) {
        $commonSubstring = "";
    }

    if(!empty($commonSubstring)) {
       $this->template .= "(.*?)".preg_quote($commonSubstring, "#"); 
       $this->commonText .= $commonSubstring;
    }
    $this->makeRightPartOfTemplate($text1Right, $text2Right, $commonSubstring);
}

// Рекурсивное построение левой части шаблона
protected function makeLeftPartOfTemplate($text1, $text2, $commonSubstring) {
    if(empty($commonSubstring)) {
        return;
    }

    $text1Left = substr($text1, 0, strpos($text1, $commonSubstring));
    $text1LeftLength = strlen($text1Left);
    $text2Left = substr($text2, 0, strpos($text2, $commonSubstring));
    $text2LeftLength = strlen($text2Left);

    if(($text1LeftLength <= $this->minLengthToSave) || ($text2LeftLength <= $this->minLengthToSave)) {
        return;
    }
    
    $text1Left = strrev($text1Left);
    $text2Left = strrev($text2Left);

    $commonSubstring = "";
    $stop = false;
    for($i = 0; $i < $text1LeftLength; $i++) {
        $x = $i;
        for($j = 0; $j < $text2LeftLength; $j++) {
            for($y = $j; $y < $text2LeftLength; $y++) {
                if($x >= $text1LeftLength) break;
                if($text1Left[$x] == $text2Left[$y]) {
                    $commonSubstring .= $text1Left[$x];
                    $x++;
                } else {
                    if(!empty($commonSubstring)) break;
                }
            }
            if(strlen($commonSubstring) >= $this->minLengthToSave) {
                $stop = true;
            }
            $x = $i;
            if($stop) break;
            $commonSubstring = "";
        }
        if($stop) break;
    }

    if(strlen($commonSubstring) < $this->minLengthToSave) {
        $commonSubstring = "";
    }

    $commonSubstring = strrev($commonSubstring);

    $text1Left = strrev($text1Left);
    $text2Left = strrev($text2Left);
    
    if(!empty($commonSubstring)) {
       $this->template = preg_quote($commonSubstring, "#")."(.*?)".$this->template; 
       $this->commonText = $commonSubstring.$this->commonText;
    }
    $this->makeLeftPartOfTemplate($text1Left, $text2Left, $commonSubstring);
}

// Получение массива с различиями
protected function getDifferent() {
    preg_match($this->template, $this->text1, $diffInText1);
    preg_match($this->template, $this->text2, $diffInText2);
    //var_dump($this->template); die();
    $countOfDifferent = count($diffInText1);
    for($i = 1; $i < $countOfDifferent; $i++) {
        $this->different[$i-1][0] = $diffInText1[$i];
        $this->different[$i-1][1] = $diffInText2[$i];
    }
}

public function  returnCommonText() {
    return $this->commonText;
}

public function returnTemplate() {
    return $this->template;
}

public function returnDifferent() {
    return $this->different;
}

}

?>