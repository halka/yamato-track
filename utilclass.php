<?php
    require_once 'vendor/autoload.php';
        
    class UtilClass{

        public $authjsonfile;
        public $sheetid;
        public $sheet;
        public $speakurl;
        private $conf;

        public function __construct(){
            $this->conf = \Symfony\Component\Yaml\Yaml::parse(file_get_contents(dirname(__FILE__).'/conf.yaml'));
            $this->authjsonfile = dirname(__FILE__).'/'.$this->conf['gauthjson'];
            $this->sheetid = $this->conf['sheetid'];
            $this->speakurl = $this->conf['speakurl'];
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
            $client = Google_Spreadsheet::getClient($this->authjsonfile);
            $file = $client->file($this->sheetid);
            $items = $file->sheet("Sheet1")->items;
            $messages = '';
            $notderivered = 0;
            foreach($items as $item) {
                if($item['isDerivered'] === "FALSE") {
                    $notderivered++;
                    $slipno = wordwrap($item['SlipNo'], 4, '-', true);
                    $datetime = str_replace('/','月',$item['Date']).'日'.$item['Time'];
                    $status = $item['Status'];
                    $itemtype = $item['Item'];
                    $placename = $item['PlaceName'];
                    $messages.="伝票番号 ${slipno} ${itemtype}の${datetime}時点のステータスは${status}です。担当店は${placename}です。 ";
                    }
                }
                if($notderivered == 0) {
                    $messages = "登録された荷物はすべて配達が完了しています。";
                }
                $this->speakGoogleHome($messages);                
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