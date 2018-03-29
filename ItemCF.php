<?php

/**
 * Class ItemCF
 * @date 2018/3/29 上午9:38
 *
 * 算法流程:
 * 1)  读取行为日志, 格式: raw_uid(raw user_id) | raw_iid(raw item id)  | raw_rating
 * 2）计算物品共现矩阵
 * 3）计算物品相似度矩阵
 */
class ItemCF
{
    // 默认参数
    const DEFAULT_K = 10; // 找K邻近
    const DEFAULT_M = 100; // 返回M个推荐商品

    // 日志的3个字段
    static private $fieldNames = ['raw_uid', 'raw_iid', 'raw_rating'];

    // 正排字典
    private $user2Items = [];

    // 倒排字典
    private $item2Users = [];

    // 物品相似度矩阵
    private $simMatrix = [];

    // 分数排序方法
    private function ratingCompare($l, $r) {
        if ($l < $r) {
            return 1;
        } else if ($l > $r) {
            return -1;
        } else {
            return 0;
        }
    }

    // 加载原始日志
    public function loadRawData($filename) {
        $fp = fopen($filename, 'r');
        if (empty($fp)) {
            return false;
        }
        $ret = true;
        while ($line = fgets($fp)) {
            $fields = preg_split('/[\s,]+/', trim($line), -1, PREG_SPLIT_NO_EMPTY);
            if (empty($fields) || count($fields) < count(self::$fieldNames)) {
                $ret = false;
                break;
            }
            // 正排字典
            $record = array_combine(self::$fieldNames, $fields);
            $this->user2Items[$record['raw_uid']][$record['raw_iid']] = $record['raw_rating'];
            // 倒排字典
            $this->item2Users[$record['raw_iid']][$record['raw_uid']] = true;
        }
        fclose($fp);
        return $ret;
    }

    // 构建物品相似度矩阵
    public function buildSimMatrix() {
        // 共现矩阵
        foreach ($this->user2Items as $raw_uid => $items) {
            foreach ($items as $raw_iid_i => $raw_rating_i) {
                foreach ($items as $raw_iid_j => $raw_iid_j) {
                    if ($raw_iid_i == $raw_iid_j) {
                        continue;
                    }
                    if (!isset($this->simMatrix[$raw_iid_i][$raw_iid_j])) {
                        $this->simMatrix[$raw_iid_i][$raw_iid_j] = 0;
                    }
                    ++$this->simMatrix[$raw_iid_i][$raw_iid_j];
                }
            }
        }

        // 相似度矩阵
        foreach ($this->simMatrix as $raw_iid_i => $items) {
            foreach ($items as $raw_iid_j => $times) {
                $this->simMatrix[$raw_iid_i][$raw_iid_j] /= sqrt(count($this->item2Users[$raw_iid_i]) * count($this->item2Users[$raw_iid_j]));
            }
            // 按相似度从高到低排序
            uasort($this->simMatrix[$raw_iid_i], [$this, 'ratingCompare']);
        }
    }

    // 序列化物品相似度矩阵,以便复用
    public function dumpSimMatrix($filename) {
        $model = json_encode($this->simMatrix);
        return file_put_contents($filename, $model);
    }

    // 加载物品相似度矩阵
    public function loadSimMatrix($filename) {
        $model = file_get_contents($filename);
        if (empty($model)) {
            return false;
        }
        $model = json_decode($model, true);
        if (!is_array($model)) {
            return false;
        }
        $this->simMatrix = $model;
        return true;
    }

    // 为某个用户推荐商品
    public function recommendByUser($raw_uid, $k = self::DEFAULT_K, $m = self::DEFAULT_M) {
        // 训练集中没有用户历史数据
        if (!isset($this->user2Items[$raw_uid])) {
            return [];
        }
        // 用户感兴趣的商品
        $raw_iids = array_keys($this->user2Items[$raw_uid]);
        $raw_ratings = array_values($this->user2Items[$raw_uid]);
        return $this->recommendByUserItems($raw_uid, $raw_iids, $raw_ratings, $k, $m);
    }

    // 根据某个用户对某些商品的兴趣, 推荐其他商品 （用户uid不要在训练数据中出现, 非常适合于为新增用户做实时推荐, 即前台根据新用户浏览行为, 基于现有商品相似矩阵完成推荐）
    public function recommendByUserItems($raw_uid, $raw_iids, $raw_ratings, $k = self::DEFAULT_K, $m = self::DEFAULT_M) {
        // 推荐物品打分
        $est_ratings = [];

        // 在相似度矩阵找每个商品最近的其他商品
        foreach ($raw_iids as $i => $raw_iid) {
            if (empty($this->simMatrix[$raw_iid])) {
                continue;
            }

            // 找出最相似的K个物品
            foreach ($this->simMatrix[$raw_iid] as $sim_raw_iid => $sim_rating) {
                $sim_iids[] = $sim_raw_iid;
                if (count($sim_iids) >= $k) {
                    break;
                }
                // 如果用户买过该推荐商品则跳过
                if (isset($this->user2Items[$raw_uid][$sim_raw_iid])) {
                    continue;
                }
                if (!isset($est_ratings[$sim_raw_iid])) {
                    $est_ratings[$sim_raw_iid] = 0;
                }
                $est_ratings[$sim_raw_iid] += $raw_ratings[$i] * $sim_rating;
            }
        }

        // 按推荐打分排序, 取前M个商品
        uasort($est_ratings, [$this, 'ratingCompare']);

        // 最终推荐商品
        $recItems = [];
        foreach ($est_ratings as $iid => $est_rating) {
            $recItems[] = [
                'iid' => $iid,
                'est'=> $est_rating,
            ];
            if (count($recItems) >= $m) {
                break;
            }
        }
        return $recItems;
    }
}