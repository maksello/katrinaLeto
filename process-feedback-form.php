<?php
  // открываем сессию
  session_start();

  // переменная в которую будем сохранять результат работы
  $data['result']='error';

  // разрешённые типы файлов
  $allowedExtension = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");

  // директория для хранения файлов ()
  $pathToFile = $_SERVER['DOCUMENT_ROOT'].'/LETO/uploads/';

  // максимальный размер файла
  $maxSizeFile = 1024288;

  // функция для проверки длины строки
  function validStringLength($string,$min,$max) {
    $length = mb_strlen($string,'UTF-8');
    if (($length<$min) || ($length>$max)) {
      return false;
    }
    else {
      return true;
    }
  }

  // если данные были отправлены методом POST, то...
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $data['result']='success';

    //получить имя, которое ввёл пользователь
    if (isset($_POST['name'])) {
      $name = $_POST['name'];
      if (!validStringLength($name,2,30)) {
        $data['name']='Поля имя содержит недопустимое количество символов.';
        $data['result']='error';
      }
    } else {
      $data['result']='error';
      $data['result1']='error';
    }
    //получить email, которое ввёл пользователь
    if (isset($_POST['email'])) {
      $email = $_POST['email'];
      if (!filter_var($email,FILTER_VALIDATE_EMAIL)) {
        $data['email']='Поле email введено неправильно';
        $data['result']='error';

      }
    } else {
      $data['result']='error';
      $data['result2']='error';
    }
    //получить сообщение, которое ввёл пользователь
    if (isset($_POST['message'])) {
      $message = $_POST['message'];
      if (!validStringLength($message,20,500)) {
        $data['message']='Поле сообщение содержит недопустимое количество символов.';
        $data['result']='error';
      }
    } else {
      $data['result']='error';
            $data['result3']='error';
    }
    //получить капчу, которую ввёл пользователь
    // если не существует ни одной ошибки, то прододжаем...
    if ($data['result']=='success') {

      // если пользователь ввёл правильный код капчи, то...

        //обработаем файлы, загруженные пользователем посредством элементов с name="images[]"

        // если ассоциатианый массив $_FILES["images"] существует, то
        if(isset($_FILES["images"])) {
          // переберём все файлы (изображения)
          $files = array();
          foreach ($_FILES["images"]["error"] as $key => $error) {
            // если ошибок не возникло, т.е. файл был успешно загружен на сервер, то...
            if ($error == UPLOAD_ERR_OK) {
              // имя файла на устройстве пользователя
              $nameFile = $_FILES['images']['name'][$key];
              // расширение загруженного пользователем файла в нижнем регистре
              $extFile = mb_strtolower(pathinfo($nameFile, PATHINFO_EXTENSION));
              // размер файла
              $sizefile = $_FILES['images']['size'][$key];
              //myme-тип файла
              $filetype = $_FILES['images']['type'][$key];
              // проверить расширение файла, размер файла и mime-тип
              if (!array_key_exists($extFile, $allowedExtension)) {
                $data['files']='Ошибка при загрузке файлов (неверное расширение).';
                $data['result']='error';
                      $data['result7']='error';
              } elseif ($sizefile > $maxSizeFile) {
                $data['files']='Ошибка при загрузке файлов (размер превышает 512Кбайт).';
                $data['result']='error';
                $data['result8']='error';
              } elseif (!in_array($filetype, $allowedExtension)){
                $data['files']='Ошибка при загрузке файлов (неверный тип файла).';
                $data['result']='error';
                $data['result9']='error';
              } else {
                //ошибок не возникло, продолжаем...

                // временное имя, с которым принятый файл был сохранён на сервере
                $tmpFile = $_FILES['images']['tmp_name'][$key];
                // уникальное имя файла
                $newFileName = uniqid('img_', true).'.'.$extFile;
                // полное имя файла
                $newFullFileName = $pathToFile.$newFileName;
                // перемещаем файл в директорию
                if (!move_uploaded_file($tmpFile, $newFullFileName)) {
                  // ошибка при перемещении файла
                  $data['files']='Ошибка при загрузке файлов.';
                  $data['result']='error';
                  $data['result10']='error';
                } else {
                  $files[] = $newFullFileName;
                }
              }
            } else {
              //ошибка при загрузке файл на сервер
              $data['result']='error';
              $data['result11']='error';
            }
          }
        }
      }
  } else {
    //ошибка не существует ассоциативный массив $_POST["send-message"]
    $data['result']='error';
    $data['result12']='error';
  }

  // дальнейшие действия (ошибок не обнаружено)
  if ($data['result']=='success') {
    //1. Сохрание формы в файл
    $output = "---------------------------------" . "\n";
    $output .= date("d-m-Y H:i:s") . "\n";
    $output .= "Имя пользователя: " . $name . "\n";
    $output .= "Адрес email: " . $email . "\n";
    $output .= "Сообщение: " . $message . "\n";
    if (isset($files)) {
      $output .= "Файлы: " . "\n";
      foreach ($files as $value) {
         $output .= $value . "\n";
      }
    }
    if (file_put_contents(dirname(__FILE__).'/info/message.txt', $output, FILE_APPEND | LOCK_EX)) {
      $data['result']='success';
    } else {
      $data['result']='error';
    }
    //2. Отправляем на почту

    // включить файл PHPMailerAutoload.php
    require_once dirname(__FILE__) . '/phpmailer/PHPMailerAutoload.php';

    //формируем тело письма
    $output = "Дата: " . date("d-m-Y H:i") . "\n";
    $output .= "Имя пользователя: " . $name . "\n";
    $output .= "Адрес email: " . $email . "\n";
    $output .= "Сообщение: " . "\n" . $message . "\n";

    // если не используем прикрепление файлов
    // отправляем файлы в качестве ссылок в теме письма
    /*if (isset($files)) {
      $output .= "Файлы: " . "\n";
      foreach ($files as $value) {
        $href = substr($value,strpos($value, '/feedback/'));
        $output .= '<p><a href="'.$href.'">'.$href.'</a></p>' . "\n";
      }
    }*/

    // создаём экземпляр класса PHPMailer
    $mail = new PHPMailer;

    $mail->From      = 'email@mysite.ru';
    $mail->FromName  = 'Имя сайта';
    $mail->Subject   = 'Сообщение с формы обратной связи';
    $mail->Body      = $output;
    $mail->AddAddress( 'snigurmk@gmail.com' );

    // прикрепляем файлы к письму
    if (isset($files)) {
      foreach ($files as $value) {
         $output .= $value . "\n";
         $mail->addAttachment($value);
      }
    }

    // отправляем письмо
    if ($mail->Send()) {
      $data['result']='success';
    } else {
      $data['result']='error';
    }
  }

  echo json_encode($data);

?>