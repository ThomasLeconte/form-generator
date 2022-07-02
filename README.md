# Form generator
> **composer require thomasleconte/form-generator**

This is a library that make you able to generate a form. This library use reflection principle to generate the best result from a class name or an existing object.  
This form generator is compatible with Doctrine ORM. In fact, if one of your property doesn't have a PHP type, but a Doctrine type annotation, it will take this one.
Moreover, in the case of a select list generation, you can fill the list with Doctrine by specifying class name, key attribute and attribute value.  

**Check this out !**

## Summary
[Construction](#construction)  
[Basic generation](#basic-generation)  
[Personalize inputs](#personalize-your-inputs)  
[Personalize form](#personalize-your-form)  
[Surround your inputs / form](#surround-your-inputs--form)  
[Special inputs](#special-inputs)  
[Hydrate dynamically select field](#select-field)  


## Construction
```php
$generator = new FormGenerator();
```
By default, constructor doesn't need arguments. But you can provide a current Doctrine EntityManager instance.
> If you installed Doctrine but you did not provide EntityManager instance to generator, it will try to get it from `bootstrap.php` file at root of your project. This file
> is generally provided by most of Doctrine tutorials online, and return an instance of Doctrine EntityManager.  
> For more informations, [check this tutorial](https://www.doctrine-project.org/projects/doctrine-orm/en/2.9/reference/configuration.html).

## Basic generation
```php
// generate(objectOrClassName, form action, fields options, form options)
$generator->generate(User::class, "/register");
$generator->show();
```

Or with a current object :
```php
$user = new User("Elon", "Musk", 50);

$generator->generate($user, "/register");
$generator->show();
```

You can add attribute to a field after generated a form. Update is also available :
```php
...
$generator->generate($user, "/register");
...
$generator->addAttribute("firstname", "placeholder", "Your first name");
$generator->updateAttribute("firstname", "placeholder", "I don't know what to write here");
$generator->show();
```

## Personalize your inputs
By default, all inputs just have an auto-generated `name` attribute. But you can give them all attributes that you want. Imagine you want to add a class to firstname input of our User class used before :
```php
$user = new User("Elon", "Musk", 50);

$generator->generate($user, "/register", [
    "firstname" => [ "class" => "input-field" ]
]);
$generator->show();
```

You can also decide to hide one of fields. **By default, "id" field of your entity is hidden and you can't override it**. You can hide others one with "hide" field option :
````php
...
$generator->generate($user, "/register", [
    "age" => [ "hide" => true ]
]);
...
````

## Personalize your form
Like inputs personalization, you can add all attributes that you want, just like that :
```php
$user = new User("Elon", "Musk", 50);

$generator->generate($user, "/register", [/* fields options */], [
    "class" => "register-form"
]);
$generator->show();
```

## Surround your inputs / form
You can surround generation result of each inputs or form for give them div parent for example. When you use it, you **must** provide `{{content}}` which corresponding to input of form generation result. Check this out :  
- **Input surround**
```php
$user = new User("Elon", "Musk", 50);

$generator->generate($user, "/register", [
    "firstname" => [
        "class" => "input-field",
        "surround" => "<div class='form-field'>{{content}}</div>"
    ],
]);
$generator->show();
```

- **Form surround**
> NB : For form surround, it surround inside of `<form>` tags.
```php
$user = new User("Elon", "Musk", 50);

$generator->generate($user, "/register", [/* fields options */], [
    "class" => "register-form",
    "surround" => "<div class='form-inputs'>{{content}}</div>"
]);
$generator->show();
```

## Special inputs
Sometimes you will want to use `select` or `textarea` tags inside your form. For use it, just precize type of input. For example, you want a textarea for `firstname` attribute and select list for `age` attribute of our User class. Check this out :
```php
$user = new User("Elon", "Musk", 50);

$generator->generate($user, "/register", [
    "firstname" => [ "type" => "textarea" ],
    "age" => [ "type" => "select" ]
]);
$generator->show();
```


## Select field
When you make a `select` field, you must provide a list of items, with a value key for differentiate each of them, and a name key to display. Check this example :
```php
$user = new User("Elon", "Musk", 50);

$generator->generate($user, "/register", [
    "firstname" => [ "type" => "textarea" ],
    "gender" => [
        "type" => "select",
        "items" => [
            array("value" => 1, "name" => "Man"),
            array("value" => 2, "name" => "Women" ),
            array("value" => 3, "name" => "Attack Helicopter")
        ]
    ]
]);
$generator->show();
```

Moreover, you can fill items array with Doctrine. Instead of array, just give class name, and specify `optionLabel` / `optionValue` with attributes name of your class specified before. This is how to do :
```php
$generator->generate($user, "/register", [
    "firstname" => [ "type" => "textarea" ],
    "gender" => [
        "type" => "select",
        "items" => Gender::class,
        "optionLabel" => "name", // name attribute of Gender class
        "optionValue" => "id" // id attribute of Gender class
    ]
]);
$generator->show();
```