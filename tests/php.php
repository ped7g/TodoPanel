<?php

// radkovy comment TODO normalni
// TODO ukonceny radek php tagem?>

<!-- TODO text mimo PHP v HTML comment bloku -->

<?php

// TODO s falesnym /* block commentem */
$x = '/* TODO v stringu by se asi najit nemelo */;

/* TODO blokove */ unset( $x ); //TODO radkove na stejnem radku jako blokove
# TODO radkovy comment skrz mrizku

/** Doc comment blok
 * TODO az na druhem radku
 * TODO a druhe todo v stejnem bloku
 * auTODOme by naopak najit nemelo
 */

function testTODOexamplefunction(/* zobraz cely radek */) {  //TODO
}
