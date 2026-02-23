<?php
$THEME = THEMES_PATH . 'ember/views/';
foreach ($comments as $comment):
    echo theme_view($THEME . 'partials/_comment.php', ['comment' => $comment, 'depth' => 0]);
endforeach;
?>
