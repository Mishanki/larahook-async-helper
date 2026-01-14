# larahook-async-helper

### Example

```php

// Base code

echo 'a';

AsyncHelper::call(static function () {
    // Some async logic with separate database connection
    $response = Http::get('http://example.com/users');
    $data = $response->json();    
    foreach ($data as $userItem) {
       SomeUserModel::updateOrCreate($userItem);
    }

    echo 'c';
});

echo 'b';

// Result:
// 'a'
// 'b'
// 'c' // hidden echo 

```