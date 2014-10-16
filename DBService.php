<?php
class DBService{
    private $host      = '127.0.0.1';
    private $user      = 'root';
    private $pass      = 'root';
    private $dbname    = 'glide_bills_test';

    private $dbh;
    private $error;
    private $stmt;

    public function __construct(){
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname.';charset=utf8';
        $options = array(
            PDO::ATTR_PERSISTENT    => true,
            PDO::ATTR_ERRMODE       => PDO::ERRMODE_EXCEPTION
        );
        try{
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        }
        catch(PDOException $e){
            $this->error=$e->getMessage();
            //echo $this->error;
        }
    }

	public function fetch($query, $paramArray){
		$stmt=$this->dbh->prepare($query);
		$stmt->execute($paramArray);
		$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
		return $rows;
		//todo : wrap all such queries in try catch and throw a generic exception type.
	}

	//inserts using a query and param and returns the last inserted id
    public function insert($query, $params){
        try{
            $stmt = $this->dbh->prepare($query);
            $stmt->execute($params);
            return $this->dbh->lastInsertId();
        }catch(PDOException $e){
            throw new Exception($e->getMessage()); //todo:create a subexception class to handle this
        }
    }
}
