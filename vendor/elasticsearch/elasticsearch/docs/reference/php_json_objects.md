---
mapped_pages:
  - https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/php_json_objects.html
---

# Dealing with JSON arrays and objects in PHP [php_json_objects]

A common source of confusion with the client revolves around JSON arrays and objects, and how to specify them in PHP. In particular, problems are caused by empty objects and arrays of objects. This page shows you some common patterns used in {{es}} JSON API and how to convert that to a PHP representation.


## Empty Objects [_empty_objects]

The {{es}} API uses empty JSON objects in several locations which can cause problems for PHP. Unlike other languages, PHP does not have a "short" notation for empty objects and many developers are unaware how to specify an empty object.

Consider adding a highlight to a query:

```json
{
    "query" : {
        "match" : {
            "content" : "quick brown fox"
        }
    },
    "highlight" : {
        "fields" : {
            "content" : {} <1>
        }
    }
}
```

1. This empty JSON object is what causes problems.


The problem is that PHP will automatically convert `"content" : {}` into `"content" : []`, which is no longer valid {{es}} DSL. We need to tell PHP that the empty object is explicitly an object, not an array. To define this query in PHP, you would do:

```json
$params['body'] = array(
    'query' => array(
        'match' => array(
            'content' => 'quick brown fox'
        )
    ),
    'highlight' => array(
        'fields' => array(
            'content' => new \stdClass() <1>
        )
    )
);
$results = $client->search($params);
```

1. We use the generic PHP stdClass object to represent an empty object. The JSON now encodes correctly.


By using an explicit stdClass object, we can force the `json_encode` parser to correctly output an empty object, instead of an empty array. This verbose solution is the only way to acomplish the goal in PHP…​ there is no "short" version of an empty object.


## Arrays of Objects [_arrays_of_objects]

Another common pattern in {{es}} DSL is an array of objects. For example, consider adding a sort to your query:

```json
{
    "query" : {
        "match" : { "content" : "quick brown fox" }
    },
    "sort" : [  <1>
        {"time" : {"order" : "desc"}},
        {"popularity" : {"order" : "desc"}}
    ]
}
```

1. "sort" contains an array of JSON objects.


This arrangement is very common, but the construction in PHP can be tricky since it requires nesting arrays. The verbosity of PHP tends to obscure what is actually going on. To construct an array of objects, you actually need an array of arrays:

```json
$params['body'] = array(
    'query' => array(
        'match' => array(
            'content' => 'quick brown fox'
        )
    ),
    'sort' => array(    <1>
        array('time' => array('order' => 'desc')),  <2>
        array('popularity' => array('order' => 'desc')) <3>
    )
);
$results = $client->search($params);
```

1. This array encodes the `"sort" : []` array
2. This array encodes the `{"time" : {"order" : "desc"}}` object
3. This array encodes the `{"popularity" : {"order" : "desc"}}` object


If you are on PHP 5.4+, we strongly encourage you to use the short array syntax. It makes these nested arrays much simpler to read:

```json
$params['body'] = [
    'query' => [
        'match' => [
            'content' => 'quick brown fox'
        ]
    ],
    'sort' => [
        ['time' => ['order' => 'desc']],
        ['popularity' => ['order' => 'desc']]
    ]
];
$results = $client->search($params);
```


## Arrays of empty objects [_arrays_of_empty_objects]

Occasionally, you’ll encounter DSL that requires both of the previous patterns. The function score query is a good example, it sometimes requires an array of objects, and some of those objects might be empty JSON objects.

Given this query:

```json
{
   "query":{
      "function_score":{
         "functions":[
            {
               "random_score":{}
            }
         ],
         "boost_mode":"replace"
      }
   }
}
```

We can build it using the following PHP code:

```json
$params['body'] = array(
    'query' => array(
        'function_score' => array(
            'functions' => array(  <1>
                array(  <2>
                    'random_score' => new \stdClass() <3>
                )
            )
        )
    )
);
$results = $client->search($params);
```

1. This encodes the array of objects: `"functions" : []`
2. This encodes an object inside the array: `{ "random_score": {} }`
3. This encodes the empty JSON object: `"random_score": {}`


