<?php


namespace App\Http\Controllers;



use Carbon\Exceptions\ParseErrorException;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController as Func;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller {

    public static function index(Request $request) {

        //获取当前的页数
        if(is_null($request->input("page"))) {
            $page = 1;
        } else{
            $page = $request->input("page");
        }
        // 获取本页的数据
        if(is_null($request->input("name"))) {
            $name = null;
            $home = "/?";
            $num = DB::select("select count(Q.SID) as num ".
                    "from t_qrcode Q")[0]->num;
            $result = DB::select("SELECT G.TITLE AS group_name, T2.name as name, T2.goin, T2.time as time ".
                    "FROM (select GU.GROUP_SID, T1.name as name, T1.goin as goin, T1.time as time ".
                    "from (SELECT U.SID AS sid, NAME AS name, Q.GOIN AS goin, Q.CREATE_TIME as time ".
                    "FROM t_qrcode Q INNER JOIN t_td_user U ON Q.USID = U.SID order by Q.SID desc limit ?, 10) as T1 ".
                    "inner join t_td_group_user GU on GU.U_SID = T1.sid) AS T2 ".
                    "inner join t_td_group AS G ON G.SID = T2.GROUP_SID", [($page - 1) * 10]);
        } else {
            $name = $request->input("name");
            $home = "/?name=".$name."&";
            $num = DB::select("select count(Q.SID) as num ".
                    "from t_qrcode Q inner join t_td_user U on Q.USID = U.SID ".
                    "where U.NAME = ?", [$name])[0]->num;
            $result = DB::select("SELECT G.TITLE AS group_name, T2.name as name, T2.name as name, T2.goin, T2.time as time ".
                "FROM (select GU.GROUP_SID, T1.name as name, T1.goin as goin, T1.time as time ".
                "from (SELECT U.SID AS sid, NAME AS name, Q.GOIN AS goin, Q.CREATE_TIME as time ".
                "FROM t_qrcode Q INNER JOIN t_td_user U where U.NAME = ? and Q.USID = U.SID order by Q.SID desc limit ?, 10) as T1 ".
                "inner join t_td_group_user GU on GU.U_SID = T1.sid) AS T2 ".
                "inner join t_td_group AS G ON G.SID = T2.GROUP_SID", [$name, ($page - 1) * 10]);
        }

        return view("index", [
            "name" => $name,
            "result" => $result,
            "num" => $num,
            "total" => intval(($num + 9) / 10),
            "current" => $page,
            "home" => $home
        ]);
    }


//    public static function search(Request $request, $page) {
//        $name = $request->input("name");
//
//        return view("index", [
//            "name" => $name
//        ]);
//    }


    public static function toExcel(Request $request) {
        if(is_null($request->input("name"))) {
            Func::alert("数据量过大，请选择导出");
            Func::goBack();
            exit();
//            $result = DB::select("SELECT G.TITLE AS group_name, T2.name as name, T2.goin, T2.time as time ".
//                "FROM (select GU.GROUP_SID, T1.name as name, T1.goin as goin, T1.time as time ".
//                "from (SELECT U.SID AS sid, NAME AS name, Q.GOIN AS goin, Q.CREATE_TIME as time ".
//                "FROM t_qrcode Q INNER JOIN t_td_user U ON Q.USID = U.SID order by Q.SID desc) as T1 ".
//                "inner join t_td_group_user GU on GU.U_SID = T1.sid) AS T2 ".
//                "inner join t_td_group AS G ON G.SID = T2.GROUP_SID");
        } else {
            $name = $request->input("name");
            $result = DB::select("SELECT G.TITLE AS group_name, T2.name as name, T2.name as name, T2.goin, T2.time as time ".
                "FROM (select GU.GROUP_SID, T1.name as name, T1.goin as goin, T1.time as time ".
                "from (SELECT U.SID AS sid, NAME AS name, Q.GOIN AS goin, Q.CREATE_TIME as time ".
                "FROM t_qrcode Q INNER JOIN t_td_user U where U.NAME = ? and Q.USID = U.SID order by Q.SID) as T1 ".
                "inner join t_td_group_user GU on GU.U_SID = T1.sid) AS T2 ".
                "inner join t_td_group AS G ON G.SID = T2.GROUP_SID",[$name]);
        }

        dd($result);
        Func::goBack();
        exit();
//        Func::redirect("/index?name=".$name);
    }

}
