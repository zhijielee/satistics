<?php


namespace App\Http\Controllers;



use Illuminate\Foundation\Console\PackageDiscoverCommand;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController as Func;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet;

class IndexController extends Controller {


    public static function index(Request $request) {

        
        return view("index");
    }
    public static function dashboard_gate(Request $request) {
        return view("dashboard_gate");
    }
     public static function dashboard(Request $request) {
        $type= $request->input('type');
        
        switch ($type) {
            case '1':
                $title="宿舍";
                break;
            case '2':
                $title="食堂";
                break;
            case '3':
                $title="场馆";
                break;
            case '5':
                $title="教学楼";
                break;
            case '6':
                $title="校门";
                break;
            case '7':
                $title="三站一场";
                break;
            default:
                $title="";
                break;
        }
       
        $res = DB::select("SELECT * FROM t_td_user_iot LEFT JOIN t_td_user ON t_td_user_iot.ACCOUNT_ID=t_td_user.UID WHERE t_td_user_iot.IOT_TYPE = :type",['type' => $type]);
        
        return view("dashboard",["res"=>$res,"title"=>$title]);
    }



    public static function getInfoNum($start, $end, $user_sql, $location_sql, $group_sql) {
        $total_num = DB::select("select count(SID) as num from t_td_group as G inner join ".
            "(select U.CURRENT_GROUP_SID as group_id from t_qrcode Q inner join ".
            "t_td_user U on Q.USID = U.SID ".
            "where Q.CREATE_TIME between " . $start . " and ".$end . $user_sql . $location_sql.") as T ".
            "on T.group_id = G.SID where 1 = 1".$group_sql)[0]->num;

        return $total_num;
    }
    public static function info(Request $request) {
        $res = $request->input();
        dump($res); 
        // 多参数处理
        $page = is_null($request->input("page")) ? 1 : $request->input("page");
        $dateTime = $request->input("date_time");
        // 时间格式转换
        if(is_null($dateTime)) {
            $start = "'".DB::select("select CREATE_TIME from t_qrcode where CREATE_TIME is not NULL order by SID limit 1")[0]->CREATE_TIME."'";
            $end = "'".DB::select("select CREATE_TIME from t_qrcode where CREATE_TIME is not NULL order by SID desc limit 1")[0]->CREATE_TIME."'";
            $start_array = explode(" ", substr($start,1, strlen($start) - 2));
            $date = explode("-", $start_array[0]);
            $time = explode(":", $start_array[1]);
            $dateTime = $date[2] . "/" . $date[1] . "/" . $date[0] . " " . ($time[0] > '12' ? intval($time[0]) - 12 : $time[0]) .":" . $time[1] ." " . ($time[0] > '12' ? "PM":"AM") ." - ";
            $end_array = explode(" ", substr($end,1, strlen($end) - 2));
            $date = explode("-", $end_array[0]);
            $time = explode(":", $end_array[1]);
            $dateTime = $dateTime . $date[2] . "/" . $date[1] . "/" . $date[0] . " " . ($time[0] > '12' ? intval($time[0]) - 12 : $time[0]) .":" . $time[1] ." " . ($time[0] > '12' ? "PM":"AM");
        } else {
            $start_init = explode(" - ", $dateTime)[0];
            $end_init = explode(" - ", $dateTime)[1];
            $start_array = explode(" ", $start_init);
            $end_array = explode(" ", $end_init);
            $date = explode("/", $start_array[0]);
            $time = explode(":", $start_array[1]);
            $start = "'" . $date[2] . "-" . $date[1] . "-" . $date[0] . " " . ($start_array[2] == "PM" ? intval($time[0]) + 12 : $time[0]) . ":" . $time[1] .":00'";
            $date = explode("/", $end_array[0]);
            $time = explode(":", $end_array[1]);
            $end = "'" . $date[2] . "-" . $date[1] . "-" . $date[0] . " " . ($end_array[2] == "PM" ? intval($time[0]) + 12 : $time[0]) . ":" . $time[1] .":59'";
        }

        if(is_null($request->input("user_name"))) {
            $user_name = null;
            $user_sql = "";
        } else {
            $user_name = $request->input("user_name");
            $user_sql = " and NAME = '" .$user_name . "'";
        }

        if(is_null($request->input("group_name"))) {
            $group_name = null;
            $group_sql = "";
        } else {
            $group_name = $request->input("group_name");
            $group_sql = " and TITLE = '" .$group_name . "'";
        }

        if(is_null($request->input("build_name"))) {
            $build_name = null;
            $build_sql = "";
        } else {
            $build_name = $request->input("build_name");
            $build_sql = " and BUILDING = '" .$build_name . "'";
        }

        $param = [
            "page" => $page,
            "date_time" => $dateTime,
            "user_name" => $user_name,
            "group_name" => $group_name,
            "build_name" => $build_name
        ];

//        $total_num = DB::select("select count(SID) as num from t_td_group as G inner join ".
//                        "(select U.CURRENT_GROUP_SID as group_id from t_qrcode Q inner join ".
//                        "t_td_user U on Q.USID = U.SID ".
//                        "where Q.CREATE_TIME between " . $start . " and ".$end . $user_sql . $build_name.") as T ".
//                        "on T.group_id = G.SID where 1 = 1".$group_name)[0]->num;
        if($page == 1) {
            $total_num = self::getInfoNum($start, $end, $user_sql, $build_sql, $group_sql);
        } else {
            $total_num = $request->input("total_num");
        }
        $result = DB::select("select T.user_id as user_id, T.user_name as user_name, T.bulid_name as build_name, T.goin as goin, T.time as time, T.body as body, TITLE as group_name from t_td_group as G inner join ".
            "(select U.SID as user_id, U.NAME as user_name, Q.BUILDING as bulid_name, U.CURRENT_GROUP_SID as group_id, Q.GOIN as goin, Q.BODY as body, Q.CREATE_TIME as time from t_qrcode Q inner join ".
            "t_td_user U on Q.USID = U.SID ".
            "where Q.CREATE_TIME between " . $start . " and ".$end . $user_sql . $build_sql." order by Q.SID desc limit ".($page - 1) * 10 .", 10) as T ".
            "on T.group_id = G.SID where 1 = 1".$group_sql);

        $url = "user_name=".$user_name."&group_name=".$group_name."&date_time=".$dateTime."&build_name=".$build_name;
        return view("info", [
            "param" => $param,
            "excel_url" => "/excel?".$url,
            "home" => "/info?total_num=".$total_num."&".$url,
            "current" => $page,
            "total_num" => $total_num,
            "total" => intval(($total_num + 9) / 10),
            "result" => $result
        ]);
    }

    public static function toExcel(Request $request, Excel $excel) {
        $is_large = 1;
        $dateTime = $request->input("date_time");
        // 时间格式转换
        if(is_null($dateTime)) {
            $start = "'".DB::select("select CREATE_TIME from t_qrcode where CREATE_TIME is not NULL order by SID limit 1")[0]->CREATE_TIME."'";
            $end = "'".DB::select("select CREATE_TIME from t_qrcode where CREATE_TIME is not NULL order by SID desc limit 1")[0]->CREATE_TIME."'";
            $start_array = explode(" ", substr($start,1, strlen($start) - 2));
            $date = explode("-", $start_array[0]);
            $time = explode(":", $start_array[1]);
            $dateTime = $date[2] . "/" . $date[1] . "/" . $date[0] . " " . ($time[0] > '12' ? intval($time[0]) - 12 : $time[0]) .":" . $time[1] ." " . ($time[0] > '12' ? "PM":"AM") ." - ";
            $end_array = explode(" ", $start);
            $date = explode("-", $end_array[0]);
            $time = explode(":", $end_array[1]);
            $dateTime = $dateTime . $date[2] . "/" . $date[1] . "/" . $date[0] . " " . ($time[0] > '12' ? intval($time[0]) - 12 : $time[0]) .":" . $time[1] ." " . ($time[0] > '12' ? "PM":"AM");
        } else {
            $is_large = 0;
            $start_init = explode(" - ", $dateTime)[0];
            $end_init = explode(" - ", $dateTime)[1];
            $start_array = explode(" ", $start_init);
            $end_array = explode(" ", $end_init);
            $date = explode("/", $start_array[0]);
            $time = explode(":", $start_array[1]);
            $start = "'" . $date[2] . "-" . $date[0] . "-" . $date[1] . " " . ($start_array[2] == "PM" ? intval($time[0]) + 12 : $time[0]) . ":" . $time[1] .":00'";
            $date = explode("/", $end_array[0]);
            $time = explode(":", $end_array[1]);
            $end = "'" . $date[2] . "-" . $date[0] . "-" . $date[1] . " " . ($end_array[2] == "PM" ? intval($time[0]) + 12 : $time[0]) . ":" . $time[1] .":59'";
        }

        if(is_null($request->input("user_name"))) {
            $user_name = null;
            $user_sql = "";
        } else {
            $is_large = 0;
            $user_name = $request->input("user_name");
            $user_sql = " and NAME = '" .$user_name . "'";
        }

        if(is_null($request->input("group_name"))) {
            $group_name = null;
            $group_sql = "";
        } else {
            $is_large = 0;
            $group_name = $request->input("group_name");
            $group_sql = " and TITLE = '" .$group_name . "'";
        }

        if(is_null($request->input("build_name"))) {
            $build_name = null;
            $build_sql = "";
        } else {
            $is_large = 0;
            $build_name = $request->input("build_name");
            $build_sql = " and BUILDING = '" .$build_name . "'";
        }

        if($is_large == 1) {
            Func::alert("数据过大，请做导出数据选择");
            Func::goBack();
            exit();
        }

        $result = DB::select("select T.user_id as user_id, T.user_name as user_name, T.bulid_name as build_name, T.goin as goin, T.time as time, T.body as body, TITLE as group_name from t_td_group as G inner join ".
            "(select U.SID as user_id, U.NAME as user_name, Q.BUILDING as bulid_name, U.CURRENT_GROUP_SID as group_id, Q.GOIN as goin, Q.BODY as body, Q.CREATE_TIME as time from t_qrcode Q inner join ".
            "t_td_user U on Q.USID = U.SID ".
            "where Q.CREATE_TIME between " . $start . " and ".$end . $user_sql . $build_name." ) as T ".
            "on T.group_id = G.SID where 1 = 1".$group_name);


        set_time_limit(0);
        foreach ($result as $key => $val) {
            $arrData[] = [
                "id" => $val->user_id,
                "name" => $val->user_name,
                "time" => $val->time,
                "goin" => $val->goin,
                "body" => $val->body,
                "group" => $val->group_name,
                "build" => $val->build_name
            ];
        }
        $title = [
            [
                '工号/学号','姓名','时间','进出类型','体温','单位','楼宇'
            ]
        ];
        $arrData = array_merge($title, $arrData);
        $spreadsheet = new PhpSpreadsheet\Spreadsheet();
        $styleArray = [
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
        ];

        $spreadsheet->getActiveSheet()->getStyle('A1:G1')->applyFromArray($styleArray);
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(25);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(25);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(25);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(25);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(25);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(25);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(25);
        $spreadsheet->getActiveSheet()->fromArray($arrData);
        $writer = new PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Description: File Transfer');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename=数据详情.xlsx');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        $fp = fopen('php://output', 'a');//打开output流
        mb_convert_variables('GBK', 'UTF-8', $columns);
        fputcsv($fp, $columns);//将数据格式化为csv格式并写入到output流中

        $dataNum = count( $arrData );
        $perSize = 2000;//每次导出的条数
        $pages = ceil($dataNum / $perSize);

        for ($i = 1; $i <= $pages; $i++) {
            foreach ($arrData as $item) {
                mb_convert_variables('GBK', 'UTF-8', $item);
                fputcsv($fp, $item);
            }
            //刷新输出缓冲到浏览器
            ob_flush();
            flush();//必须同时使用 ob_flush() 和flush() 函数来刷新输出缓冲。
        }
        fclose($fp);
        Func::goBack();
        exit();
    }

}
