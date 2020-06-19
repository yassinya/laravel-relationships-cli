This package allows you to define relationships between your models directly from the command line instead of creating them then setting the relationship methods manually.

Most of the Laravel relationships are supported, including polymorphic ones.

# Usage
To define a relationship you'll need to use the relation command which expects 3 required arguments:
1. The main model's name
2. The type of the relationship
> **The following abbreviations are used for the relationships**

> - One To One -> 121

> - One to Many -> 12m

> - Many to Many -> m2m

3. Name of the target model 

## One to One

```
php artisan relation User 121 Phone
```
> It'll first try to find The models then edit them, and if they don't exist they'll be created

## One to Many

```
php artisan relation Library 12m Book
```

## Many to Many

```
php artisan relation User m2m Product
```

## Polymorphic Relationships
To define a polymorphic relationship just add the **-p** or **--polymorphic** option. For instance
to define a 121 polymorphic relationship between Post and Image 

```
php artisan relation Post 121 Image -p
```

Between User and Image 

```
php artisan relation User 121 Image -p
```

