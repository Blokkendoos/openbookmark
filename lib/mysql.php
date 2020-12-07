<?php

class mysql
{

    var $error = "";
    var $result = false;

    function mysql()
    {
        global $dsn;
        global $dbh;
        if (! $dbh = mysqli_connect($dsn['hostspec'], $dsn['username'], $dsn['password'])) {
            $this->error = mysqli_error($dbh);
        }
        if (! mysqli_select_db($dbh, $dsn['database'])) {
            $this->error = mysql_error($dbh);
        }
    }

    function query($query)
    {
        global $dbh;
        if ($this->result = mysqli_query($dbh, $query)) {
            return true;
        } else {
            $this->error = mysqli_error($dbh);
            return false;
        }
    }

    function escape($string)
    {
        global $dbh;
        return mysqli_real_escape_string($dbh, $string);
    }
}

// Compatibility layer with PHP < 5.3
if (!function_exists('mysqli_result')) {
    function mysqli_result($result, $col = 0)
    {
        $row = $result->fetch_row();
        if (isset($row[$col])) {
            return $row[$col];
        }
        return null;
    }
}

if (!function_exists('mysqli_fetch_all')) {
    function mysqli_fetch_all($result, $resulttype = MYSQLI_NUM)
    {
        for ($res = array(); $tmp = $this->fetch_array($result, $resulttype);) {
            $res[] = $tmp;
        }
        return $res;
    }
}
