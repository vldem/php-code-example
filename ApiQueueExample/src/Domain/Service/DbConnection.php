<?php

namespace App\Domain\Service;

use App\Domain\Service\Helper;

/**
 * This class provides a connection and methods to work with mysql database.
 */
class DbConnection
{
    /**
     * @var \mysqli
     */
    protected \mysqli $dbHandler;

    /**
     * @var string
     */
    public string $error;

    /**
     * @var Helper
     */
    private Helper $helper;

    /**
     * Constructor
     * Establish connection with database through mysqli PHP module.
     * @param string $host  The host name for DB connection.
     * @param string $username  The user name for DB connection.
     * @param string $password  The password for DB connection.
     * @param string $database  The database name for DB connection.
     * @param Helper $helper  The helper.
     *
     * @throws \InvalidArgumentException  if connection parameters are empty.
     * @throws \RuntimeException          if some error occurred during establishing connection to database.
     */
    public function __construct( Helper $helper )
    {
        $this->helper = $helper;
    }

    /**
     * Establish connection with database.
     * @param string $host  Host name, localhost for example
     * @param string $user  User name
     * @param string $password
     * @param string $dbname  Database name
     *
     * @throws \InvalidArgumentException  if connection parameters are empty.
     * @throws \RuntimeException          if some error occurred during establishing connection to database.
     *
     * @return true|false  true if the connection to DB has been establish. false if connection already exists.
     *
     */
    public function openConnection($host, $user, $password, $dbname): bool
    {
        if ( empty($host) || empty($user) || empty($password) || empty($dbname) ) {
            throw new \InvalidArgumentException('Database connection parameters are missing.');
        }

        if ( !isset( $this->dbHandler ) ) {
            $this->dbHandler = new \mysqli( $host, $user, $password, $dbname );
            if ( mysqli_connect_errno() ) {
                $data = array(
                    'error' => mysqli_connect_errno(),
                    'errorMessage' => mysqli_connect_error(),
                );
                throw new \RuntimeException( json_encode($data) );
            }
            return true;
        } else {
            return false;
        }
    }

    public function closeConnection()
    {
        $this->dbHandler->close();
    }


    /**
     * Execute a query that returns some data records. Usually SELECT query.
     * @param string $sqlStr     SQL string
     * @param array  $tags        (optional) array of parameters' placeholders (like '{var1}') that will be replaced by values array
     * @param array  $values      (optional) array of values that will replace parameters' placeholders.
     * @param int    $debug       (optional) 1 - debug mode on, 0 (default) - debug mode off
     * @return array              array of rows of SQL query result. Empty array if empty result.
     * @throws \RuntimeException  if some error occurred during sql execution
     */
    public function querySelect( string $sqlStr, array $tags = array(), array $values = array(), int $debug = 0): array
    {
        //$sql = $this->setValuesToParams( $sqlStr, $tags, $values);

        //Escape special characters in a string for use in an SQL statement
        if (!empty($values) ) {
            $values = $this->prepareValues( $values );
        }

        $sql = $this->helper->setValuesToParams( $sqlStr, $tags, $values);

        $rows = false;
        if ($debug) {
            var_dump( $sql );
        }

        if ( $result = $this->dbHandler->query( $sql) ) {
            $this->error = '';
            $rows = $result->fetch_all( MYSQLI_ASSOC );
            $result->free_result();
        } else {
            $this->error = $this->dbHandler->error;
            throw new \RuntimeException( $this->error . "\nQuery is " . $sql );
        }

        return $rows;
    }

    /**
     * Execute a query that returns some data records (usually SELECT query) and get field value of specified row and column
     * @param string $sqlStr  SQL string
     * @param int $rowIndex   (optional) row index number, default value is 0 that means 1st record of the query result
     * @param int $colIndex   (optional) column index number, default value is 0 that means 1st column of the query result
     * @param array $tags     (optional) array of parameters' placeholders (like '{var1}' ) that will be replaced by values array
     * @param array $values   (optional) array of values that will replace parameters' placeholders.
     * @param int $debug      (optional) 1 - debug mode on, 0 (default) - debug mode off
     * @return string         with fields value or empty sting if empty result.
     * @throws \RuntimeException  if some error occurred during sql execution
     */
    public function queryGetValue( string $sqlStr, int $rowIndex = 0, int $colIndex = 0, array $tags = array(), array $values = array(), int $debug = 0): string
    {
        //Escape special characters in a string for use in an SQL statement
        if (!empty($values) ) {
            $values = $this->prepareValues( $values );
        }

        $sql = $this->helper->setValuesToParams( $sqlStr, $tags, $values);

        $fieldValue = '';
        if ($debug) {
            var_dump( $sql );
        }

        if ( $result = $this->dbHandler->query( $sql ) ) {
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
     * @param string $sqlStr     SQL string
     * @param int  $colIndex      (optional) column index number, default value is 0 that means 1st column of the query result
     * @param array  $tags        (optional) array of parameters' placeholders (like '{var1}' ) that will be repleced by values array
     * @param array  $values      (optional) array of values that will replace parameters' placeholders.
     * @param int    $debug       (optional) 1 - debug mode on, 0 (default) - debug mode off
     * @return array              with fields value or empty array if empty result.
     * @throws \RuntimeException  if some error occurred during sql execution
     */
    public function queryGetCol( string $sqlStr, int $colIndex = 0, array $tags = array(), array $values = array(), $debug = 0): array
    {
        //Escape special characters in a string for use in an SQL statement
        if (!empty($values) ) {
            $values = $this->prepareValues( $values );
        }

        $sql = $this->helper->setValuesToParams( $sqlStr, $tags, $values);

        if ($debug) {
            var_dump( $sql );
        }

        $colArray = array();
        if ( $result = $this->dbHandler->query( $sql ) ) {
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
     * @param string $sqlStr     SQL string
     * @param array  $tags        (optional) array of parameters' placeholders (like '{var1}') that will be repleced by values array
     * @param array  $values      (optional) array of values that will replace parameters' placeholders.
     * @param int    $debug       (optional) 1 - debug mode on, 0 (default) - debug mode off
     * @return int                0 there   are not records affected for SQL query, grater 0 number of affected rows by SQL query.
     * @throws \RuntimeException  if some error occured during sql execution
     */
    public function queryDo( string $sqlStr, array $tags = array(), array $values = array(), int $debug = 0 ): int
    {
        //Escape special characters in a string for use in an SQL statement
        if (!empty($values) ) {
            $values = $this->prepareValues( $values );
        }

        $sql = $this->helper->setValuesToParams( $sqlStr, $tags, $values);

        if ($debug) {
            var_dump($sql);
        }
        $this->dbHandler->query( $sql );
        $result = $this->dbHandler->affected_rows;
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
     * @param string  $sqlStr          SQL string
     * @param string  $bindTypesString  string of types of parameters. Like 'ssissds'? where 's' is string, 'i' - integer, 'd' - datetime.
     * @param array   $params           array of values that will be binded with ? tags.
     * @return int                      -1  parameters are empty, 0 - there are not records affected for SQL query, grater 0 - number of affected rows by SQL query.
     * @throws \RuntimeException        if some error occured during sql execution
     */
    public function queryBind( string $sqlStr, string $bindTypesString, array $params ): int
    {
        if ( isset($params) and isset($bindTypesString) ) {
            $bindParams[] = &$bindTypesString;
            $paramCount = count( $params );
            for ($i=0; $i<$paramCount; $i++ ) {
                $bindParams[] = &$params[$i];
            }
            try {
                $stmt = $this->dbHandler->prepare( $sqlStr );
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
     * Escapes special characters in a string for use in an SQL statement to prevent SQL injection.
     *
     * @param array $values  The array of values that will be substitute to query's placeholders.
     *
     * @return array  Prepared array of values
     */
    protected function prepareValues( array $values = array() ): array
    {
        if ( isset($values) ) {
            $newValues = $values;
            foreach ( $newValues as $i => $value ) {
                $newValues[$i] = $this->dbHandler->real_escape_string( $value );
            }
            return $newValues;

        } else {
            return $values;
        }

    }
}
