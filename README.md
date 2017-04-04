# Baby Steps 
## Refactoring Page-Oriented PHP

This project is a presentation for the OKC PHP user group. The intention is to
show how to move from a page-oriented coding style into a more professional, 
object-oriented and framework-based system.
 
Each commit within the project shows the progress toward this goal. Begin by
examining the initial commit and move forward.

Just to be clear, there are no guarantees that this code will actually run. :D

### Step 1

I dredged up some old files that implement an Active Record approach to database
access using PDO. Nevermind those for now. They were terrific in 2014 but I will replace
them with a real ORM before too long.
          
I've used those old PDO classes to encapsulate SQL into a ClassifiedAdRepository
and a ClassifiedAd model. This allows me to remove the SQL and all of the
deprecated mysql_* functions from classadd.php.

I've also deleted much of the dead and commented-out code for readability.

### Step 2

I've created a very basic composer.json and added the Symfony Http-Foundation
package with the following commands.

```
composer init
composer require symfony/http-foundation
```

### Step 3

Since I loaded Symfony Http-Foundation components in the last step, I put the
Request object to use to clean up the code. The greatest impact is probably in
the usage of `$request->getUri()` where a couple of lines of code were
replaced by the much more robust Symfony method.
 
There are now two areas where I think the code can be improved most. One area
is to abstract the HTML into some type of view. The other is to replace the
tedious validation code with a declarative approach.
