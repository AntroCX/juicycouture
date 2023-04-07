import {src, dest}            from 'gulp';
import {getDir, isProduction} from '../helpers/gets';
import pug                    from 'gulp-pug';
import beautify               from 'gulp-html-beautify';

import siteData from '../../src/_services/data';

/**
 * Сборка страниц
 *
 * pug:
 *    locals                  — локальные переменные. Экспортируем json'ы (списки товаров и т.п.)
 *    debug                   — дебаг режим для dev версии
 *
 * beautify:
 *    indent_size             — количество симовлов в отступе (бекендеры любят 4 пробела)
 *    indent_char             — символ отступа (табы/пробелы и т.д., у нас пробел)
 *    indent_level            — начальный уровень отступа (html -> body -> и т.д.)
 *    preserve_newlines       — сохраняем разрывы строк
 *    max_preserve_newlines   — максимальное количество разрывов
 *    end_with_newline        — пустая линия в конце файла
 *
 *
 * @param cb   — callback
 */
export function buildPages(cb) {
  const pages = isProduction()
                ? 'src/pages/[^_]*.pug'
                : 'src/pages/*.pug';

  src(pages)
    .pipe(
      pug({
        locals: siteData,
        debug:  false
      })
        .on('error', e => {
            console.log('****  Error:  ****');
            console.log(e);
            console.log('****  endError:  ****');
          }
        )
    )
    .pipe(beautify({
      indent_size:           4,
      indent_char:           ' ',
      indent_level:          0,
      preserve_newlines:     true,
      max_preserve_newlines: 2,
      end_with_newline:      true
    }))
    .pipe(dest(`${getDir()}/`));

  cb ? cb() : 0;
}