<p>После клонирования необходимо произвести следующие действия:</p>
<ul>
    <li>
        Выполнить: <strong>composer update</strong>
    </li>
    <li>
        Создать файл <strong>.env</strong> и заполнить его
    </li>
    <li>
        Выполнить: <strong>php artisan key:generate</strong>
    </li>
    <li>
        Выполнить: <strong>php artisan storage:link</strong>
    </li>      
    <li>
        Создать следующие папки:
        <ul>
            <li>
                <strong>public/css</strong>
            </li>
            <li>
                <strong>public/js</strong>
            </li>
            <li>
                <strong>resource/views</strong>
            </li>                        
        </ul>
        Создать следующие файлы:
        <ul>
            <li>
                <strong>public/favicon.ico</strong>
            </li>
            <li>
                <strong>public/.htaccess</strong>
            </li>                       
        </ul>        
    </li>
    <li>
        Из файла <strong>.gitignore</strong> удалить все вышеперечисленные пути
    </li>
</ul>
