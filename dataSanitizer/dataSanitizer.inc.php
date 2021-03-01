<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

class DataSanitizer
{
   // Format firstName and lastName
   // Will try to format both names
   //
   // return (firstName, lastName, saniValid)
   static function formatUserNames($firstName, $lastName)
   {
      $saniValid = 1;
      $msgs = "";
      foreach (array("firstName", "lastName") as $field)
      {
         // Sanitize against XSS
         $$field = DataSanitizer::filterField($$field);
         // Let's try to sanitize it
         try {
            $$field = DataSanitizer::formatName($$field);
         }
         catch (Exception $e) {
            // Oups : do not modify it, it will be checked in the future
            // Statistically : 0.5% of names only
            $saniValid = 0;
            $msgs .= $e->getMessage().";";
         }
      }
      return array($firstName, $lastName, $saniValid, $msgs);
   }
   static function formatSchool($name, $city, $country)
   {
      $saniValid = 1;
      $msgs = "";
      foreach (array("name", "city", "country") as $field)
      {
         // Sanitize against XSS
         $$field = DataSanitizer::filterField($$field);
         // Let's try to sanitize it
         try {
            $$field = DataSanitizer::formatNameComplex($$field);
         }
         catch (Exception $e) {
            // Oups : do not modify it, it will be checked in the future
            // Statistically : 0.5% of names only
            $saniValid = 0;
            $msgs .= $e->getMessage().";";
         }
      }
      // The name has been fixed, let's try to format the school category
      try {
         $name = DataSanitizer::postFormatSchoolName($name);
      }
      catch (Exception $e) {
         $saniValid = 0;
         $msgs .= $e->getMessage().";";
      }
      return array($name, $city, $country, $saniValid, $msgs);
   }

   ////////// Data sanitizing //////////
   // Some special variables
   static $particles = array("de", "du");
   static $wordsNoMaj = array();//array_merge(self::$particles, array("le", "la", "et", "les", "lès"));
   static $apostrophe =  array("d", "l"); // d', l'

   // Check if the given string respect the following "grammar"
   // Will clean and apply correct casing to the string
   //
   // - The string must be a set of words separated by spaces.
   // - Each word can be a set of sub-words separated by a dash.
   // - Each subword can start with an apostrophe "l', "d'"...
   //   and must be constituted only of letters.
   // - If "$allowNumbers" is true, number are also authorized at the start of each word.
   //   For example : "12ème" of "Cedex 20"
   // - Some special words will not start with an uppercase letter : "de", "sur"...
   // 
   // THROW AN EXCEPTION IF THE THIS FORMAT IS NOT RESPECTED
   static function formatName($s, $allowNumbers = false)
   {
      $s = self::formatNameRec($s, $allowNumbers);
      // First letter must be upper
      $s = self::strtoupper(mb_substr($s, 0, 1)).mb_substr($s, 1);
      return $s;
   }

   // Similar to "formatName" but we also accept parenthesis
   // in the string. The parenthesis must contains valid words.
   // For example :
   // "Reunion Island (France)"
   static function formatNameComplex($s, $allowNumbers = true)
   {
      $dummmy = "Azerty";
      // We allow some parenthesis in the name => country, city..
      preg_match_all('/\(([^\)]*)\)/', $s, $matches);
      $s = preg_replace('/\([^\)]*\)/', $dummmy, $s);
      $s = self::formatName($s, $allowNumbers);
      foreach ($matches[1] as $match)
      {
        $s = preg_replace("/$dummmy/", "(".self::formatName($match, $allowNumbers).")", $s, 1);
      }
      return $s;
   }

   // Utility function to remove un-necessary spaces
   static function rmMultipleSpaces($s) 
   {
      $s = trim($s);
      $s = preg_replace('/ +/', ' ', $s);
      $s = preg_replace('/ $/', '', $s);
      return $s;
   }
   // Utility function to extract the optional apostrophe
   // Will return: 
   // - the apotrophe letter (with the apostrophe in correct case
   // - the rest of the word
   static function getApos($oriWord)
   {
      $word = $oriWord;
      if (mb_substr($word, 1, 1) != "'")
         return array("", $word);      
      $letter = self::strtoupper(mb_substr($word, 0, 1));
      $word = mb_substr($word, 2);
      // Lowercase apostophe letters
      if (in_array($letter, array("D", "L")))
         $letter = self::strtolower($letter);
      else
         $letter = self::strtoupper($letter);

      return array("$letter'", $word);
   }

   // Internal function that does the work described in "formatName"
   private static function formatNameRec($s, $allowNumbers = false)
   {
      // White-spaces cleaning
      $s = self::rmMultipleSpaces($s);

      // A name contains multiple words, separated by spaces
      $words = explode(" ", $s);
      foreach ($words as $i => $word)
      {
         // If there is a "dash", let's recurse on each sub-word
         if (preg_match('/-/', $word))
         {
            $subWords = explode("-", $word);
            foreach ($subWords as $j => $sub)
            {
               $subWords[$j] = self::formatNameRec($sub, $allowNumbers);
            }
            $word = join("-", $subWords);
         }
         else
         {
            list($apos, $word) = self::getApos($word);
            // Special words that needs to be lower-case
            if (in_array(self::strtolower($word), array("le", "la", "et", "de", "du", "sur", "les", "lès", "en")))
            {
               $word = self::strtolower($word);
            }
            // Generic case : first letter is upper, others are lower
            else
            {
               $word = self::ucfirst(self::strtolower($word));
            }
            // Test letters
            $allowed = join('', self::getLower())."\-\'";
            if (!(
               preg_match('/^['.$allowed.']+$/', self::strtolower($word)) ||
               (preg_match('/^[0-9]+$/', self::strtolower($word)) && $allowNumbers)
               ) || $word == "'")
               throw new Exception("Invalid letters in '$word'");
            $word = $apos.$word;
         }
         $words[$i] = $word;
      }
      $s = join(" ", $words);
      return $s;
   }

   // Utility function to post-correct schools names with common mistakes.
   static function postFormatSchoolName($name)
   {
      // Already formatted, post-verification
      // Check if "valid", for human use only (no automatic correction)

      // Fix Typos
      $typos = array(
          "Ecole" => "École", 
          "Clg" => "Collège",     
          "College" => "Collège", 
          "Collége" => "Collège", 
          "Lycee" => "Lycée", 
          "Lpo" => "Lycée",   
          "Lgt" => "Lycée",   
          "Legt"  => "Lycée");

      $name = str_replace(array_keys($typos), array_values($typos), $name);
      $allowed = array("École", "Collège", "Lycée", "Institution", "Groupe Scolaire", "Cours", "Ensemble", "Cité Scolaire", "Internat");
      foreach ($allowed as $cat)
         if (preg_match("/^$cat /", $name))
            return $name;
      throw new Exception("Invalid school category for '$name'");
   }

   // Sanitize field, no error should be thrown there
   static function filterField($string)
   {
      $string = DataSanitizer::rmMultipleSpaces($string);
      $string = str_replace(['<', '>'], '', $string);
      return $string;
   }

   ////////// LowerCase / UpperCase functions //////////

   // List of all LowerCase caracters in Western alphabet
   static function getLower()
   {
      return array( 
         "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", 
         "v", "w", "x", "y", "z", "à", "á", "â", "ã", "ä", "å", "æ", "ç", "è", "é", "ê", "ë", "ì", "í", "î", "ï", 
         "ð", "ñ", "ò", "ó", "ô", "õ", "ö", "ø", "ù", "ú", "û", "ü", "ý", "а", "б", "в", "г", "д", "е", "ё", "ж", 
         "з", "и", "й", "к", "л", "м", "н", "о", "п", "р", "с", "т", "у", "ф", "х", "ц", "ч", "ш", "щ", "ъ", "ы", 
         "ь", "э", "ю", "я", "œ"
      ); 
   }

   // List of all UpperCase caracters in Western alphabet
   static function getUpper()
   {
      return array( 
         "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", 
         "V", "W", "X", "Y", "Z", "À", "Á", "Â", "Ã", "Ä", "Å", "Æ", "Ç", "È", "É", "Ê", "Ë", "Ì", "Í", "Î", "Ï", 
         "Ð", "Ñ", "Ò", "Ó", "Ô", "Õ", "Ö", "Ø", "Ù", "Ú", "Û", "Ü", "Ý", "А", "Б", "В", "Г", "Д", "Е", "Ё", "Ж", 
         "З", "И", "Й", "К", "Л", "М", "Н", "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ц", "Ч", "Ш", "Щ", "Ъ", "Ъ", 
         "Ь", "Э", "Ю", "Я", "Œ" 
      ); 
   }

   // UTF-8 compliant implementation of "strtolower"
   static function strtolower($string)
   { 
      return str_replace(self::getUpper(), self::getLower(), $string); 
   } 

   // UTF-8 compliant implementation of "strtoupper"
   static function strtoupper($string)
   { 
      return str_replace(self::getLower(), self::getUpper(), $string); 
   } 

   // UTF-8 compliant implementation of "ucfirst"
   static function ucfirst($string)
   {
      return self::strtoupper(mb_substr($string, 0, 1)).self::strtolower(mb_substr($string, 1));
   }

   // UTF-8 compliant implementation of "words"
   static function ucwords($string)
   {
      $words = explode(" ", $string);
      foreach ($words as $i => $word)
      {
         $words[$i] = self::ucfirst($word);
      }
      return join(" ", $words);
   }
}
?>
