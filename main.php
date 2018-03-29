<?php

require __DIR__ . "/ItemCF.php";

$itemCF = new ItemCF();

// 加载训练集
if (!$itemCF->loadRawData('./data.txt')) {
    die('loadData fail');
}

// 构建物品相似度矩阵
$itemCF->buildSimMatrix();

// 保存相似度矩阵到磁盘, 作为可复用的模型
if (!$itemCF->dumpSimMatrix('./model.dat')) {
    die('dumpSimMatrix fail');
}

// 为已知用户A作推荐
$recItemsA = $itemCF->recommendByUser('A');
print_r($recItemsA);

// 为新用户D作推荐, 我们根据最近D用户浏览行为知道D访问过张天爱的相关文章
$recItemsD = $itemCF->recommendByUserItems('D', ['张天爱'], [5,]);
print_r($recItemsD);