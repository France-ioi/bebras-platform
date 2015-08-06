<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

class Route
{
   private $route;
   private $root;
   
   /**
    * Constructor
    * 
    * @param string $root Path to the question directory
    * @param string $route The question's route (eg. )
    */
   public function __construct($root, $route)
   {
      $this->root = $root;
      $this->setRoute($route);
   }
   
   /**
    * Check whether or not the requested file is an image
    * 
    * @param string $route
    * @return boolean
    */
   public static function isImage($route)
   {
      $extensions = array('.png', '.gif', '.jpg', '.jpeg');
      $extension = self::getExtension($route);
      
      return in_array($extension, $extensions);
   }
   
   /**
    * Check whether or not the requested file is static content
    * 
    * @param string $route
    * @return boolean
    */
   public static function isFile($route)
   {
      $extensions = array('.js', '.css', '.html');
      $extension = self::getExtension($route);
      
      return in_array($extension, $extensions);
   }
   
   /**
    * Get a route's extension
    * 
    * @param string $route
    * @return string
    */
   public static function getExtension($route)
   {
      return strrchr(strtolower($route), '.');
   }
   
   /**
    * Get the absolute path of the Bebras's JSON
    * 
    * @return string
    */
   public function getJsonPath()
   {
      return $this->root.'/'.$this->route.'/bebras.json';
   }
   
   /**
    * Get the task name (eg. 2012-FR-07)
    * 
    * @return string
    */
   public function getTaskName()
   {
      // Substract "2012/" in "2012/2012-FR-07"
      return substr($this->route, 5);
   }
   
   /**
    * Get the url of the question's directory
    * 
    * @return string
    */
   public function getTaskPath()
   {      
      return 'beaver_tasks/'.$this->route;
   }
   
   /**
    * Get the task's absolute path
    * 
    * @return string
    */
   public function getTaskDir()
   {
      return $this->root.'/'.$this->route;
   }
   
   /**
    * Display the image referenced by the route
    * 
    * @throws Exception if the route is not an image
    */
   public function displayImage()
   {
      $imagePath = $this->root.'/'.$this->route;
      
      if (!self::isImage($this->route)) {
         throw new Exception('The route is not an image.');
      }
      if (!file_exists($this->root.'/'.$this->route)) {
         throw new Exception('The image '.$imagePath.' does not exists.');
      }
      
      $fp = fopen($imagePath, 'rb');
      
      // Headers
      $extension = self::getExtension($this->route);
      switch ($extension) {
         case '.png':
            header('Content-Type: image/png');
            break;
         case '.gif':
            header('Content-Type: image/gif');
            break;
         default:
            header('Content-Type: image/jpeg');
            break;
      }
      
      header('Content-Length: ' . filesize($imagePath));
      
      // Dump
      fpassthru($fp);
   }
   
   /**
    * Display the file referenced by the route
    * 
    * @throws Exception if the route is not a file
    */
   public function displayFile()
   {
      $filePath = $this->root.'/'.$this->route;
      
      if (!self::isFile($this->route)) {
         throw new Exception('The route is not a correct file.');
      }
      if (!file_exists($this->root.'/'.$this->route)) {
         throw new Exception('The file '.$filePath.' does not exists.');
      }
      
      echo file_get_contents($filePath);
   }
   
   /**
    * Get the image content referenced by the route
    * 
    * @throws Exception if the route is not an image
    */
   public function getImage()
   {
      $imagePath = $this->root.'/'.$this->route;
      
      if (!self::isImage($this->route)) {
         throw new Exception('The route is not an image.');
      }
      if (!file_exists($this->root.'/'.$this->route)) {
         throw new Exception('The image '.$imagePath.' does not exists.');
      }
      
      return file_get_contents($imagePath);
   }
   
   /**
    * Set the route
    * Additionnal verifications are needed if the requested route is not an image
    * 
    * @param string $route
    * @throws Exception if the route is not an image, nor a question directory
    */
   public function setRoute($route)
   {
      // Handle images
      if (!$this->isImage($route) && !$this->isFile($route)) {
         // Route must follow a directory of a question inside the questions directory
         $curQuestionDir = $this->root.'/'.$route;
         if (!is_dir($curQuestionDir)) {
            throw new Exception('Question directory '.$curQuestionDir.' does not exists.');
         }
         if (!file_exists($curQuestionDir.'/bebras.js')) {
            throw new Exception('Question directory '.$curQuestionDir.' is not a task.');
         }
         
         // A question's route must end with a "/"
         if ($route[strlen($route) - 1] != '/') {
            throw new Exception('A question\'s directory must end with a "/".');
         }
         $route = substr($route, 0, strlen($route) - 1);
      }
      
      $this->route = $route;
   }
   
   public function getRoute()
   {
      return $this->route;
   }
}