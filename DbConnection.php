<?php

namespace App\UnitTest\Modules;

/**
 * This class provides a connection and methods to work with mysql database.
 */
class DbConnection
{
    protected $dbHandler;
    public $error;

    /**
     * Constructor
     * Establish connection with database through mysqli PHP module.
     * @param array $connectionConfig     contains database connection parameters.
     * @throws \InvalidArgumentException  if connection parameters are empty.
     * @throws \RuntimeException          if some error occurred during establishing connection to database.
     */
    public function __construct( array $connectionConfig ) {

        if ( empty($connectionConfig) ) {
            throw new \InvalidArgumentException('Database connection parameters are missing.');
        }

        $result = $this->openConnection(
            $connectionConfig['host'],
            $connectionConfig['user'],
            $connectionConfig['password'],
            $connectionConfig['database']
        );
        if ($result !== true && $result !== false) {
            throw new \RuntimeException( json_encode($result) );
        }
    }

    /**
     * Establish connection with database.
     * @param string $host  Host name, localhost for example
     * @param string $user  User name
     * @param string $password
     * @param string $dbname  Database name
     * @return true|false|array true if the connection to DB has been establish. false if connection already exists.
     *                          Array with error message from mysqli if some error occurred.
     */
    public function openConnection($host, $user, $password, $dbname) {
        if ( !isset( $this->dbHandler ) ) {
            $this->dbHandler = new \mysqli( $host, $user, $password, $dbname );
            if ( mysqli_connect_errno() ) {
                $data = array(
                    'error' => mysqli_connect_errno(),
                    'errorMessage' => mysqli_connect_error(),
                );
                return $data;
            }
            return true;
        } else {
            return false;
        }
    }

    public function closeConnection() {
        $this->dbHandler->close();
    }


    /**
     * Execute a query that returns some data records. Usually SELECT query.
     * @param string $sqlStr      SQL string
     * @param array  $tags        (optional) array of parameters' placeholders (like '{var1}') that will be replaced by values array
     * @param array  $values      (optional) array of values that will replace parameters' placeholders.
     * @param int    $debug       (optional) 1 - debug mode on, 0 (default) - debug mode off
     * @return array/false        array of rows of SQL query result. false if empty result.
     * @throws \RuntimeException  if some error occurred during sql execution
     */
    public function querySelect($sqlStr, $tags = array(), $values = array(), $debug = 0) {
        $sql = $this->setValuesToParams( $sqlStr, $tags, $values);

        $rows = false;
        if ($debug) {
            var_dump( $sql );
        }
        if ( $result = mysqli_query($this->dbHandler, $sql) ) {
            $this->error = '';
            $rows = mysqli_fetch_all( $result, MYSQLI_ASSOC );
            mysqli_free_result( $result );
            if ( empty($rows) ) {
                $rows = false;
            }
        } else {
            $this->error = $this->dbHandler->error;
            throw new \RuntimeException( $this->error . "\nQuery is " . $sql );
        }
        return $rows;
    }

    /**
     * Execute a query that returns some data records (usually SELECT query) and get field value of specified row and column
     * @param string $sqlStr      SQL string
     * @param array  $rowIndex    (optional) row index number, default value is 0 that means 1st record of the query result
     * @param array  $colIndex    (optional) column index number, default value is 0 that means 1st column of the query result
     * @param array  $tags        (optional) array of parameters' placeholders (like '{var1}' ) that will be replaced by values array
     * @param array  $values      (optional) array of values that will replace parameters' placeholders.
     * @param int    $debug       (optional) 1 - debug mode on, 0 (default) - debug mode off
     * @return string             with fields value or empty sting if empty result.
     * @throws \RuntimeException  if some error occurred during sql execution
     */
    public function queryGetValue( $sqlStr, $rowIndex = 0, $colIndex = 0, $tags = array(), $values = array(), $debug = 0) {
        $sql = $this->setValuesToParams( $sqlStr, $tags, $values);

        $fieldValue = '';
        if ($debug) {
            var_dump( $sql );
        }
        if ( $result = mysqli_query( $this->dbHandler, $sql) ) {
            $this->error = '';
            if ( $colIndex > $result->field_count - 1 ) {
                throw new \RuntimeException( 'Error: There is no a column with index ' . $colIndex . " in the query result\nQuery is " . $sql );
            }
            if( $rowIndex > $result->num_rows - 1 ) {
                throw new \RuntimeException( 'Error: There is no a row with index ' . $rowIndex . " in the query result\nQuery is " . $sql );
            }
            $result->data_seek( $rowIndex );
            $fieldValue = ( $result->fetch_row() )[ $colIndex ];
        } else {
            $this->error = $this->dbHandler->error;
            throw new \RuntimeException( $this->error . "\nQuery is " . $sql );
        }
        return $fieldValue;
    }

    /**
     * Execute a query that returns some data records (usually SELECT query) and get specified column of query result.
     * @param string $sqlStr      SQL string
     * @param array  $colIndex    (optional) column index number, default value is 0 that means 1st column of the query result
     * @param array  $tags        (optional) array of parameters' placeholders (like '{var1}' ) that will be repleced by values array
     * @param array  $values      (optional) array of values that will replace parameters' placeholders.
     * @param int    $debug       (optional) 1 - debug mode on, 0 (default) - debug mode off
     * @return array              with fields value or empty array if empty result.
     * @throws \RuntimeException  if some error occurred during sql execution
     */
    public function queryGetCol( $sqlStr, $colIndex = 0, $tags = array(), $values = array(), $debug = 0) {
        $sql = $this->setValuesToParams( $sqlStr, $tags, $values);

        if ($debug) {
            var_dump( $sql );
        }

        $colArray = array();
        if ( $result = mysqli_query( $this->dbHandler, $sql) ) {
            if ( $colIndex > $result->field_count - 1 ) {
                throw new \RuntimeException( 'Error: There is no a column with index ' . $colIndex . " in the query result\nQuery is " . $sql );
            }
            for ( $i=0, $j = $result->num_rows; $i < $j; $i++ ) {
                $row = $result->fetch_array(MYSQLI_NUM);
                if ( false === $row ) {
                    $this->error = $this->dbHandler->error;
                    throw new \RuntimeException( $this->error . "\nQuery is " . $sql );
                }
                $colArray[] = $row[ $colIndex ];
            }
        } else {
            $this->error = $this->dbHandler->error;
            throw new \RuntimeException( $this->error . "\nQuery is " . $sql );
        }
        return $colArray;
    }


    /**
     * Execute a query that does not return some data records. Usually CREATE, UPDATE, INSERT queries.
     * @param string $sqlStr      SQL string
     * @param array  $tags        (optional) array of parameters' placeholders (like '{var1}') that will be repleced by values array
     * @param array  $values      (optional) array of values that will replace parameters' placeholders.
     * @param int    $debug       (optional) 1 - debug mode on, 0 (default) - debug mode off
     * @return int                0 there   are not records affected for SQL query, grater 0 number of affected rows by SQL query.
     * @throws \RuntimeException  if some error occured during sql execution
     */
    public function queryDo( $sqlStr, $tags = array(), $values = array(), $debug = 0 ) {

        $sql = $this->setValuesToParams( $sqlStr, $tags, $values);

        if ($debug) {
            var_dump($sql);
        }
        mysqli_query( $this->dbHandler, $sql );
        $result = mysqli_affected_rows ($this->dbHandler );
        if( $result == -1 ) {
            $this->error = $this->dbHandler->error;
            throw new \RuntimeException( $this->error . "\nQuery is " . $sql );
        } else {
            $this->error = '';
        }

        return $result;
    }

    /**
     * Execute query that requires to bind some variables to SQL parameters. Usually INSERT, REPLACE, UPDATE queries.
     * @param string  $sqlStr           SQL string
     * @param string  $bindTypesString  string of types of parameters. Like 'ssissds'? where 's' is string, 'i' - integer, 'd' - datetime.
     * @param array   $params           array of values that will be binded with ? tags.
     * @return int                      -1  parameters are empty, 0 - there are not records affected for SQL query, grater 0 - number of affected rows by SQL query.
     * @throws \RuntimeException        if some error occured during sql execution
     */
    public function queryBind( $sqlStr, $bindTypesString, $params ) {
        if ( isset($params) and isset($bindTypesString) ) {
            $bindParams[] = &$bindTypesString;
            $p_cnt = count( $params );
            for ($i=0; $i<$p_cnt; $i++ ) {
                $bindParams[] = &$params[$i];
            }
            try{
                $stmt = mysqli_prepare( $this->dbHandler, $sqlStr );
                call_user_func_array( array($stmt, 'bind_param'), $bindParams );
                $stmt->execute();
                $rows = $stmt->affected_rows;
                $stmt->close();
                return $rows;
            }
            catch ( Exception $e ){
                throw new \RuntimeException( $e->getMessage(). "\nQuery is " . $sqlStr );
            }
        } else {
            return -1;
        }
    }

    /**
     * Substitute values to parameters tags in the string.
     * @param string $sqlStr   SQL string
     * @param array  $tags     array of parameter tags (like '{var1}') that will be replaced by values array
     * @param array  $values   array of values that will replace parameter tags.
     * @return string          updated sql string.
     */
    private function setValuesToParams( $sqlStr, $tags = array(), $values = array() ) {

        if ( isset($tags) and isset($values) ) {
            $cntTags = count($tags);
            $cntVal = count($values);
            if ( $cntTags > 0 and $cntVal > 0 and $cntTags == $cntVal ) {
                $sqlStr = str_replace($tags, $values, $sqlStr);
            }
        }
        return $sqlStr;
    }

}
