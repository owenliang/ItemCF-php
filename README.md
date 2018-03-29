# ItemCF-php

simple itemCF algorithm in php

# 说明

一个单机版的物品协同过滤DEMO，目标是生成物品相似度矩阵。

线上用户可以快速根据浏览的商品ID，从矩阵推荐若干相似商品给用户，既简单又实用。

# 输入

```
A 周杰伦 5
A 林俊杰 2
B 蔡依林 3
B 周杰伦 2
B 林俊杰 4
B 张天爱 3
B 喜洋洋 1
C 周杰伦 5
C 佟丽娅 3
C 蔡依林 5
```

# 物品相似度矩阵

```
{
	"周杰伦": {
		"林俊杰": 0.8164965809277261,
		"蔡依林": 0.8164965809277261,
		"张天爱": 0.5773502691896258,
		"喜洋洋": 0.5773502691896258,
		"佟丽娅": 0.5773502691896258
	},
	"林俊杰": {
		"周杰伦": 0.8164965809277261,
		"张天爱": 0.7071067811865475,
		"喜洋洋": 0.7071067811865475,
		"蔡依林": 0.5
	},
	"蔡依林": {
		"周杰伦": 0.8164965809277261,
		"张天爱": 0.7071067811865475,
		"喜洋洋": 0.7071067811865475,
		"佟丽娅": 0.7071067811865475,
		"林俊杰": 0.5
	},
	"张天爱": {
		"喜洋洋": 1,
		"蔡依林": 0.7071067811865475,
		"林俊杰": 0.7071067811865475,
		"周杰伦": 0.5773502691896258
	},
	"喜洋洋": {
		"张天爱": 1,
		"蔡依林": 0.7071067811865475,
		"林俊杰": 0.7071067811865475,
		"周杰伦": 0.5773502691896258
	},
	"佟丽娅": {
		"蔡依林": 0.7071067811865475,
		"周杰伦": 0.5773502691896258
	}
}
```

# 为已知用户推荐

```
// 为已知用户A作推荐
$recItemsA = $itemCF->recommendByUser('A');
print_r($recItemsA);

Array
(
    [0] => Array
        (
            [iid] => 蔡依林
            [est] => 5.0824829046386
        )

    [1] => Array
        (
            [iid] => 张天爱
            [est] => 4.3009649083212
        )

    [2] => Array
        (
            [iid] => 喜洋洋
            [est] => 4.3009649083212
        )

    [3] => Array
        (
            [iid] => 佟丽娅
            [est] => 2.8867513459481
        )

)
```

# 为新用户推荐

``` 
// 为新用户D作推荐, 我们根据最近D用户浏览行为知道D访问过张天爱的相关文章
$recItemsD = $itemCF->recommendByUserItems('D', ['张天爱'], [5,]);
print_r($recItemsD);

Array
(
    [0] => Array
        (
            [iid] => 喜洋洋
            [est] => 5
        )

    [1] => Array
        (
            [iid] => 蔡依林
            [est] => 3.5355339059327
        )

    [2] => Array
        (
            [iid] => 林俊杰
            [est] => 3.5355339059327
        )

    [3] => Array
        (
            [iid] => 周杰伦
            [est] => 2.8867513459481
        )

)
```