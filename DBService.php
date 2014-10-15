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
	}
}
