<?php
/**
 * Potato
 * One-time-password self-service and administration
 * Version 1.0
 * 
 * Written by Markus Berg
 *   email: markus@kelvin.nu
 *   http://kelvin.nu/software/potato/
 * 
 * Copyright 2011 Markus Berg
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 */

// All demo users
$demo = array("alice" => array('pw'=>"fu",     'admin'=>false, 'fullName'=>"Alice Alpha"),
              "bob"   => array('pw'=>"bar",    'admin'=>false, 'fullName'=>"Robert Bravo"),
              "carol" => array('pw'=>"fubar",  'admin'=>true,  'fullName'=>"Carol Charlie"),
              "dave"  => array('pw'=>"rofl",   'admin'=>false, 'fullName'=>"David Delta"),
              "eve"   => array('pw'=>"lolcat", 'admin'=>true,  'fullName'=>"Eve Echo"));


?>
