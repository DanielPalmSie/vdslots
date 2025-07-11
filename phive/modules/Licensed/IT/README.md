## Code organization

The code is organized in order to decoupling the libraries (PACG and PGDA) from the framework.
The connection point between the framework and the libraries is the code contained in the "IT.php class".

```
App classes --> IT.php --> Libraries
```

In the future, if we want to move the libraries in another place we have only to fix the part of the "IT.php class" that implement the libraries.

Also, if the business logic of the application will change, we don't have to update the libraries but only how it is set up the values.


|                         | IT Class | Libraries |
:-------------------------|:--------:|:---------:|
| **Query**               |     x    |           |
| **Phive**               |     x    |           |
| **Framework features**  |     x    |           |
| **Pacg Instance**       |     x    |           |
| **Pgda Instance**       |     x    |           |

### IT.php Class

In this class, or in the traits included in the class, there are all the methods callable from the framework. In this class you can:
- Create an instance of Pacg class;
- Create an instance of Pgda class;
- Use Phive functions;
- Execute database query;
- Use other framework features.

### PACG Folder
The code related to PACG (Protocol for PACG Gambling Account Id Registration). 

In the folder :
``` 
pacg > lib
``` 
there is the class Pacg. To use the PACG methods, you must create an instance of this class.

In this class you can't:
- Execute database query;
- Use Phive functions;
- Use other framework features;

### PGDA Folder
The code related to PGDA (Communication Protocol between Licensee's Processing System and ADM Central System);

In the folder :
``` 
pgda > lib
``` 
there is the Pgda class. To use the PGDA methods, you must create an instance of this class.

In this class you can't:
- Execute database query;
- Use Phive functions;
- Use other framework features;

