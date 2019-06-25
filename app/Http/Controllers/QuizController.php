<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Snoopy;
use App\Model\Word;
use App\Model\Line;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public $snoopy;
    public function __construct()
    {
        $this->snoopy = new Snoopy;
    }

    public function english(Request $request)
    {
        $id = 1;
        $ran_box = $request->input('ran_box[]');

        $vocas = word::where('wbook_pk', $id)->select('w_nm AS word')->groupBy('w_nm')->get()->toArray();

        if (empty($ran_box)) {
            $ran_box = [];
        }

        $random = random_int(0, count($vocas) - 1);

        while (in_array($random, $ran_box)) {
            $random = random_int(0, $vocas->count() - 1);
        }

        array_push($ran_box, $random);

        $word = array_splice($vocas, $random, 1);

        \Log::debug($vocas);
        \Log::debug($word);

        $randoms = array_rand($vocas, 3);
        $choiceRandom = random_int(0, 3);

        $choices = [$vocas[$randoms[0]], $vocas[$randoms[1]], $vocas[$randoms[2]]];

        $arr_front = array_slice($choices, 0, $choiceRandom);
        $arr_end = array_slice($choices, $choiceRandom);

        \Log::debug($choiceRandom);
        $arr_front[] = $word[0];
        \Log::debug($arr_front);
        $choices = array_merge($arr_front, $arr_end);

        \Log::debug($vocas);

        $this->snoopy->fetch('https://m.dic.daum.net/search.do?q=' . $word[0]['word']);
        $result = $this->snoopy->results;

        $matchFlag = preg_match('/<ul class="list_search">(.*?)<\/ul>/is', $result, $mean);
        /*태그만제거*/
        $mean = preg_replace("/<ul[^>]*>/i", '', $mean);
        $mean = preg_replace("/<\/ul>/i", '', $mean);
        $mean = preg_replace("/<\/li>/", '', $mean);
        $mean = preg_replace("/<span[^>]*>/i", '', $mean);
        $mean = preg_replace("/<\/span>/", '', $mean);
        $mean = preg_replace("/(<daum[^>]*>)/i", '', $mean);
        $mean = preg_replace("/(<\/daum:word>)/", '', $mean);
        $mean = preg_replace("/[0-9]./", '', $mean);
        $mean = preg_replace("/\t|\n/", '', $mean);

        if ($matchFlag) {
            $array = explode('<li>', $mean[0]);
            array_shift($array);

            $back = ["ques" => $array, "choice" => $choices, "ans" => $choiceRandom, "ran_box" => $ran_box];
            $back = json_encode($back, JSON_UNESCAPED_UNICODE);
            return $back;
        } else {
            return '검색 결과가 없습니다.';
        }

    }

    public function sentence() // 문장 빈칸 끼워맞추기
    {
        $id = 1;

        $line = line::select('line', 'explain', 'pic_add')->inRandomOrder()->take(1)->get();
        $ques = explode(' ', $line[0]->line);
        $ques[count($ques)-1] = preg_replace("/\?/", '', $ques[count($ques)-1]);
        //\Log::debug($ques);
        $question = random_int(0, count($ques)-1);
        $quest[] = $ques[$question];
        strtoupper($ques[$question]);
        // \Log::debug($quest);

        $example = line::select('line')->inRandomOrder()->take(2)->get();

        $ex1 = explode(' ', $example[0]->line);
        $ex1[count($ex1)-1] = preg_replace("/\?/", '', $ex1[count($ex1)-1]);
        $ex2 = explode(' ', $example[1]->line);
        $ex2[count($ex2)-1] = preg_replace("/\?/", '', $ex2[count($ex2)-1]);
        $exam = array_merge($ex1, $ex2);
        //strtoupper($exam);
        \Log::debug($exam);
        $exam = array_unique($exam);
        \Log::debug($exam);
        $examm = array_intersect($exam, $quest);
        \Log::debug($exam);
        if($examm) {
            $key = array_keys($examm);
            //\Log::debug($key[0]);
            unset($exam[$key[0]]);
        }
        \Log::debug($examm);

        \Log::debug($exam);

        $choice = array_rand($exam, 3);
        \Log::debug($choice);

        $choiceRandom = random_int(0, 3);

        $choi = [$exam[$choice[0]], $exam[$choice[1]], $exam[$choice[2]]];

        $num = array_search($quest, $choi);
        //\Log::debug($num);

        $arr_front = array_slice($choi, 0, $choiceRandom);
        $arr_end = array_slice($choi, $choiceRandom);

        array_push($arr_front, $ques[$question]);
        $choices = array_merge($arr_front, $arr_end);

        $back = ["ques" => $ques, "choice" => $choices, "block" => $question, "ans" => $choiceRandom, "explain" => $line[0]->explain, "picture" => $line[0]->pic_add];
        $back = json_encode($back, JSON_UNESCAPED_UNICODE);

        return $back;
    }

    public function japanese()
    {
        $id = 2;
        $vocas = word::where('wbook_pk', $id)->select('w_nm')->inRandomOrder()->take(4)->get();
        $random = random_int(0, $vocas->count() - 1);
        $quiz = $vocas->slice($random, 1);
        $this->snoopy->fetch('https://alldic.daum.net/search.do?q=' . $quiz[$random]->w_nm . '&dic=jp');
        $result = $this->snoopy->results;

        $matchFlag = preg_match('/<ul class="list_search">(.*?)<\/ul>/is', $result, $mean);
        $mean = preg_replace("/<ul[^>]*>/i", '', $mean);
        $mean = preg_replace("/<\/ul>/i", '', $mean);
        $mean = preg_replace("/<\/li>/", '', $mean);
        $mean = preg_replace("/<span[^>]*>/i", '', $mean);
        $mean = preg_replace("/<\/span>/", '', $mean);
        $mean = preg_replace("/(<daum[^>]*>)/i", '', $mean);
        $mean = preg_replace("/(<\/daum:word>)/", '', $mean);
        $mean = preg_replace("/[0-9]./", '', $mean);
        $mean = preg_replace("/\t|\n/", '', $mean);

        if ($matchFlag) {
            $array = explode('<li>', $mean[0]);
            array_shift($array);

            $back = ["ques" => $array, "choice" => $vocas, "ans" => $random];
            $back = json_encode($back, JSON_UNESCAPED_UNICODE);
            return $back;
        } else {
            return '검색 결과가 없습니다.';
        }

    }

    public function result(Request $request)
    {
        $results = $request->input('results');

        $result = (int) $results;
        \Log::debug(gettype($result) . $result);

        \DB::insert('insert into votest_result_tb (m_id, test_add, test_score) values (?, ?, ?)', [1, "numnum", $result]);

        return "저장되었습니다";
    }

    public function wrong() {
        $id = 1;

        $word = word::where('wbook_pk', $id)->select('w_nm AS word')->groupBy('w_nm')->inRandomOrder()->take(1)->get();

        $words = word::where('wbook_pk', $id)->select('w_nm AS word')->groupBy('w_nm')->inRandomOrder()->take(5)->get()->toArray();
        //\Log::debug($vocas);

        // $examm = array_intersect($words, $word[0]);
        // //\Log::debug($exam);
        // if($examm) {
        //     $key = array_keys($examm);
        //     //\Log::debug($key[0]);
        //     unset($words[$key[0]]);
        // }

        //array_push($ran_box, $random);

        //$word = array_splice($vocas, $random, 1);

        $this->snoopy->fetch('https://m.dic.daum.net/search.do?q=' . $word[0]['word']);
        $result = $this->snoopy->results;

        $matchFlag = preg_match('/<ul class="list_search">(.*?)<\/ul>/is', $result, $mean);
        /*태그만제거*/
        $mean = preg_replace("/<ul[^>]*>/i", '', $mean);
        $mean = preg_replace("/<\/ul>/i", '', $mean);
        $mean = preg_replace("/<\/li>/", '', $mean);
        $mean = preg_replace("/<span[^>]*>/i", '', $mean);
        $mean = preg_replace("/<\/span>/", '', $mean);
        $mean = preg_replace("/(<daum[^>]*>)/i", '', $mean);
        $mean = preg_replace("/(<\/daum:word>)/", '', $mean);
        $mean = preg_replace("/[0-9]./", '', $mean);
        $mean = preg_replace("/\t|\n/", '', $mean);

        if ($matchFlag) {
            $array = explode('<li>', $mean[0]);
            array_shift($array);
            //\Log::debug($array);
            $choice = array_rand($array, 3);
            $choiceRandom = random_int(0, 3);

            

            $choi = [$array[$choice[0]], $array[$choice[1]], $array[$choice[2]]];


            $arr_front = array_slice($choi, 0, $choiceRandom);
            $arr_end = array_slice($choi, $choiceRandom);

            $this->snoopy->fetch('https://m.dic.daum.net/search.do?q=' . $words[$choiceRandom]['word']);
            $result = $this->snoopy->results;

            $matchFlag = preg_match('/<ul class="list_search">(.*?)<\/ul>/is', $result, $mean);
            /*태그만제거*/
            $mean = preg_replace("/<ul[^>]*>/i", '', $mean);
            $mean = preg_replace("/<\/ul>/i", '', $mean);
            $mean = preg_replace("/<\/li>/", '', $mean);
            $mean = preg_replace("/<span[^>]*>/i", '', $mean);
            $mean = preg_replace("/<\/span>/", '', $mean);
            $mean = preg_replace("/(<daum[^>]*>)/i", '', $mean);
            $mean = preg_replace("/(<\/daum:word>)/", '', $mean);
            $mean = preg_replace("/[0-9]./", '', $mean);
            $mean = preg_replace("/\t|\n/", '', $mean);

            if ($matchFlag) {
                $maan = explode('<li>', $mean[0]);
                array_shift($maan);
                $man = array_rand($maan, 1);
            } else {
                return '검색 결과가 없습니다.';
            }


            $arr_front[] = $maan[$man];
            //\Log::debug($arr_front);

            // if (isset($arr_front)) {
            //     $word[3] = "---";
            // }

            $choices = array_merge($arr_front, $arr_end);

            $back = ["ques" => $word[0]['word'], "choice" => $choices, "ans" => $choiceRandom];
            //$back = ["ques" => $array];
            $back = json_encode($back, JSON_UNESCAPED_UNICODE);
            return $back;
        } else {
            return '검색 결과가 없습니다.';
        }

    }

}
