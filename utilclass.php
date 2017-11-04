<?php
    require_once 'vendor/autoload.php';
        
    class UtilClass{

        public $authjsonfile;
        public $sheetid;
        public $sheet;
        public $speakurl;

        public function __construct(){
            $this->authjsonfile = dirname(__FILE__).'/yamatotrack-c49951835862.json';
            $this->sheetid = '1dDGoXdE03y52II9tSumDJt0v2z22opGbtuTre_XElIg';
            $this->speakurl = 'https://7464d9ea.ap.ngrok.io/google-home-notifier';
        }

        function debugger(){
            var_dump($this->sheet);
        }

        function trackBySplitNumber($slipNumber){
            //thx! http://nanoappli.com/blog/archives/603
            $result_json = file_get_contents('http://nanoappli.com/tracking/api/'.str_replace('-', '', $slipNumber).'.json');
            $decoded_json = json_decode($result_json, true);
            $last_status = count($decoded_json['statusList'])-1;
            $placename = $decoded_json['statusList'][$last_status]['placeName'];
            if(empty($placename)) $placename = $this->centerSearch($decoded_json['statusList'][$last_status]['placeCode']);
            $status_array = array('status' => $decoded_json['status'],
                'item' => $decoded_json['itemType'],
                'date' => $decoded_json['statusList'][$last_status]['date'],
                'time' => $decoded_json['statusList'][$last_status]['time'],
                'placename' => $placename,
            );
            return $status_array;
        }

        function updateSpreadSheet(){
            $client = Google_Spreadsheet::getClient($this->authjsonfile);
            $file = $client->file($this->sheetid);
            $sheet = $file->sheet("Sheet1");
            $this->sheet = &$sheet->items; 
    
            foreach($sheet->items as $key => $item){
                if($item['isDerivered'] === 'FALSE' or empty($item['isDerivered']))
                {
                        $result = $this->trackBySplitNumber($item['SlipNo']);
                        // delivered?
                        $delivered_flag = preg_match('/.+完了/u', $result['status'])? 'TRUE':'FALSE';
                        // update spreadsheet
                        $sheet->update($key, 'SlipNo', $item['SlipNo']);
                        $sheet->update($key, 'Item', $result['item']);
                        $sheet->update($key, 'Date',$result['date']);
                        $sheet->update($key, 'Time', $result['time']); 
                        $sheet->update($key, 'Status', $result['status']); 
                        $sheet->update($key, 'PlaceName', $result['placename']);
                        $sheet->update($key, 'isDerivered', $delivered_flag);
                }
            }
        }

        function centerSearch($placeCode){
            // thx! http://nanoappli.com/blog/archives/1022
            $place_json = file_get_contents('http://nanoappli.com/tracking/api/center/'.$placeCode.'.json');
            $place_name = json_decode($place_json, true);
            return $place_name['centerName'];
        }

        function messageMaker(){
            foreach($this->sheet as $item){
                if($item['isDerivered'] === "FALSE"){
                    $slipno = wordwrap($item['SlipNo'], 4, '-', true);
                    $datetime = str_replace('/','月',$item['Date']).'日'.$item['Time'];
                    $status = $item['Status'];
                    $placename = $item['PlaceName'];
                    return "伝票番号 ${slipno}の${datetime}時点のステータスは${status}です。担当店は${placename}です。";
                }else{
                    return "まだ届いていない荷物はありません。";
                }
            }
        }

        function speakGoogleHome($message){
            $url = $this->speakurl;
            $data = "text=".$message;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_exec($ch);
            curl_close($ch);
        }
    }
?>