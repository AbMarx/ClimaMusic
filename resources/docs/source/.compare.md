---
title: API Reference

language_tabs:
- bash
- javascript
- php
- python

includes:

search: true

toc_footers:
- <a href='http://github.com/mpociot/documentarian'>Documentation Powered by Documentarian</a>
---
<!-- START_INFO -->
# Info

Welcome to the generated API reference.
[Get Postman Collection](http://localhost/docs/collection.json)

<!-- END_INFO -->

#general


<!-- START_89148c770ad57a1c32f7518d531c2756 -->
## weather/{city}
> Example request:

```bash
curl -X GET \
    -G "http://localhost/weather/1" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "http://localhost/weather/1"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'http://localhost/weather/1',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```python
import requests
import json

url = 'http://localhost/weather/1'
headers = {
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}
response = requests.request('GET', url, headers=headers)
response.json()
```


> Example response (500):

```json
null
```

### HTTP Request
`GET weather/{city}`


<!-- END_89148c770ad57a1c32f7518d531c2756 -->

<!-- START_4e9fc3df6ebb2a43640615862b5850b2 -->
## statistics
> Example request:

```bash
curl -X GET \
    -G "http://localhost/statistics" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json"
```

```javascript
const url = new URL(
    "http://localhost/statistics"
);

let headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```

```php

$client = new \GuzzleHttp\Client();
$response = $client->get(
    'http://localhost/statistics',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ],
    ]
);
$body = $response->getBody();
print_r(json_decode((string) $body));
```

```python
import requests
import json

url = 'http://localhost/statistics'
headers = {
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}
response = requests.request('GET', url, headers=headers)
response.json()
```


> Example response (500):

```json
null
```

### HTTP Request
`GET statistics`


<!-- END_4e9fc3df6ebb2a43640615862b5850b2 -->


