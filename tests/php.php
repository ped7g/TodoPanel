<?php

// radkovy comment TODO normalni
// TODO ukonceny radek php tagem?>

<!-- TODO text mimo PHP v HTML comment bloku -->

<?php

// TODO s falesnym /* block commentem
//TODO a bez uzavreni falesneho bloku
/* blokovy comment
 * // s falesnym radkovym TODO nic */
/* /* TODO blokovy nested comment /* (bad practice) */
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
