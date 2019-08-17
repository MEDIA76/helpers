<?php

/**
 * Markdown 19.08.3 Arcane Helper
 * https://github.com/MEDIA76/arcane
**/

return function($content) {
  if(!is_array($content)) {
    $content = trim($content);

    if(substr($content, -2) === 'md') {
      if(is_file($path = path($content, true))) {
        $content = file_get_contents($path);
      }
    }

    $content = explode("\n", $content);
  }

  $content = array_values(array_merge(array_filter($content), [0]));

  foreach($content as $index => $line) {
    $line = rtrim($line);

    if($line && $index !== array_key_last($content)) {
      $next = rtrim($content[$index + 1]);

      $linestart = ltrim($line)[0] ?? 0;
      $nextstart = ltrim($next)[0] ?? 0;

      if($linestart === '>') {
        $line = substr($line, strpos($line, '>') + 1);

        if(!isset($quote)) {
          $quote = 'blockquote';
          $results[] = "<{$quote}>";
        }
      }

      if(ctype_space(substr($line, 0, 4))) {
        $line = htmlentities(substr($line, 4));
        $format = "\n%s";

        if(!isset($code)) {
          $code = 'pre';
          $results[] = "<{$code}><code>";
        }
      } else {
        preg_match('/^[\s]*(\#{1,6}|\+|\-|\*)\s+(.+)$/', $line, $part);

        if(!empty($part)) {
          $partstart = $part[1];

          if($partstart[0] === '#') {
            $length = strlen($partstart);
            $format = "<h{$length}>%s</h{$length}>";
          } else if(in_array($partstart, ['+', '-', '*'])) {
            $format = "<li>%s</li>";

            if(!isset($primary) || !isset($secondary)) {
              $list = $partstart === '*' ? 'ul' : 'ol';

              if($partstart === '-') {
                $list = "{$list} type=\"A\"";
              }

              if(!isset($primary)) {
                $format = "<{$list}>{$format}";
                $primary = $partstart;
              }

              if(!isset($secondary)) {
                if($linestart !== $primary) {
                  $format = "<{$list}>{$format}";
                  $secondary = $partstart;
                }
              }
            }

            if(isset($secondary) || isset($primary)) {
              if(trim($next[0]) || !$next) {
                if(isset($secondary)) {
                  if($nextstart !== $secondary) {
                    $list = $secondary === '*' ? 'ul' : 'ol';
                    $format = "{$format}</{$list}>";

                    unset($secondary);
                  }
                }

                if($nextstart !== $primary) {
                  $list = $primary === '*' ? 'ul' : 'ol';
                  $format = "{$format}</{$list}>";

                  unset($primary);
                }
              }
            }
          }

          $line = $part[2];
        } else {
          $line = ltrim($line);

          if($line === '---') {
            $format = '<hr />';
          } else {
            $format = '<p>%s</p>';
          }
        }

        foreach([
          '*' => '/\*([^ \*+][^ \*+]*[^ \*+]?)\*/',
          '_' => '/\_([^ \_+][^ \_+]*[^ \_+]?)\_/',
          '~' => '/\~([^ \~+][^ \~+]*[^ \~+]?)\~/',
          '`' => '/\`([^ \`+][^ \`+]*[^ \`+]?)\`/',
          '![' => '/\!\[(.*)\]\((.*)\)/',
          '](' => '/\[(.*)\]\((.*)\)/'
        ] as $search => $regex) {
          if(strpos($line, $search) !== false) {
            if($search === '`') {
              $line = preg_replace_callback($regex, function($match) {
                $match = htmlentities($match[1]);

                return str_replace('$1', $match, '<code>$1</code>');
              }, $line);
            } else {
              $html = [
                '*' => '<strong>$1</strong>',
                '_' => '<em>$1</em>',
                '~' => '<strike>$1</strike>',
                '![' => '<img src="$2" alt="$1" />',
                '](' => '<a href="$2">$1</a>'
              ];

              $line = preg_replace($regex, $html[$search], $line);
            }
          }
        }
      }

      $results[] = sprintf($format, $line);

      if(isset($code)) {
        if(!ctype_space(substr($next, 0, 4))) {
          $results[] = "</{$code}></code>";

          unset($code);
        }
      }

      if(isset($quote)) {
        if($nextstart !== '>') {
          $results[] = "</{$quote}>";

          unset($quote);
        }
      }
    }
  }

  return implode($results);
};

?>