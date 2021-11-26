<?php
namespace app\controller\api;

use think\facade\Db;

class Device extends Base{

    public function __construct()
    {
        parent::__construct();
        $this->table = "dev_sn";  
    }


    public function add(){

        $data = $_POST;

        $res = Db::table($this->table)->insert($data);

        if($res){
            respond(1000,"success");
        }else{
            respond(10010,"fail");
        }
    }

    public function edit(){
        
        $data = $_POST;

        $res = Db::table($this->table)->insert($data);

        if($res){
            respond(1000,"success");
        }else{
            respond(10010,"fail");
        }
    }


}