<?php
/**
 * Potato
 * One-time-password self-service and administration
 * Version 1.0
 * 
 * Written by Markus Berg
 *   email: markus@kelvin.nu
 *   http://kelvin.nu/potato/
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

class NavigationBar {
    private $numRows;
    private $rowsPerPage = 100;
    private $pageCurrent;
    private $pageTotal;
    private $getParams = array();

    function getRowsOffset() {
        return ($this->pageCurrent - 1 )*$this->rowsPerPage;
    }

    function getRowsPerPage() {
        return $this->rowsPerPage;
    }

    function addGetParam($key, $value) {
        $this->getParams[] = "${key}=" . urlencode( $value );
    }

    function setNumRows($rows) {
        $this->numRows = $rows;
        $this->pageTotal = ceil($this->numRows / $this->rowsPerPage);
    }

    function setPageCurrent($page) {
        $this->pageCurrent = is_numeric($page) ? $page : 1;
        $this->pageCurrent = ($this->pageCurrent > $this->pageTotal ? $this->pageTotal : $this->pageCurrent);
        $this->pageCurrent = ($this->pageCurrent < 1 ? 1 : $this->pageCurrent);
    }
    
    function getEntry($page, $title = "") {
        if ($page < 1 or $page > $this->pageTotal) {
            return "";
        }
        $params = $this->getParams;
        $params[] = "page=" . $page;
        $url = "?" . implode('&', $params);
        $title = $title == "" ? $page : $title;
        return "      <td><a href=\"${url}\">" . $title . "</a></td>\n";
    }
    
    function printNavBar() {
        if ( $this->pageTotal == 1 ) {
            return;
        }
        echo "<div class=\"navbarContainer\">\n";
        echo "  <table class=\"navbar\">\n";
        echo "    <tr>\n";
        echo $this->pageCurrent == 1 ? "<td class=\"current\">« first</td>" : $this->getEntry(1, "« first");
        echo $this->pageCurrent - 4 > 1 ? "<td class=\"spacer\">...</td>\n" : "";
        echo $this->getEntry($this->pageCurrent - 4);
        echo $this->getEntry($this->pageCurrent - 3);
        echo $this->getEntry($this->pageCurrent - 2);
        echo $this->getEntry($this->pageCurrent - 1);
        echo "      <td class=\"current\">" . $this->pageCurrent . "</td>\n";
        echo $this->getEntry($this->pageCurrent + 1);
        echo $this->getEntry($this->pageCurrent + 2);
        echo $this->getEntry($this->pageCurrent + 3);
        echo $this->getEntry($this->pageCurrent + 4);
        echo $this->pageCurrent + 4 < $this->pageTotal ? "<td class=\"spacer\">...</td>\n" : "";
        echo $this->pageCurrent == $this->pageTotal ? "<td class=\"current\">last »</td>" : $this->getEntry( $this->pageTotal, "last »" );
        echo "    </tr>\n";
        echo "  </table>\n";
        echo "</div>\n";
    }
}

?>
