<?php

require('vendor/autoload.php');

$request = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
session_start();

switch( $request )
{
    case "/get":
        header('Content-Type: text/javascript');
        if(!$_SESSION['auth'])die('{"result":false,"error":"unauthorized"}');
        if(!$_GET['id'])die('{"result":false,"error":"Missing ID"}');else $id=$_GET['id'];
        $dotenv = new Dotenv\Dotenv(__DIR__);
        try
        {
            $dotenv->load();
            $dotenv->required(['CONSUMER_SECRET', 'CONSUMER_KEY']);
        } catch (RuntimeException $e)
        {
            die('{"result":false,"error":"Internal Error"}');
        }
        \Codebird\Codebird::setConsumerKey($_ENV["CONSUMER_KEY"], $_ENV["CONSUMER_SECRET"]);
        $cb = \Codebird\Codebird::getInstance();
        $cb->setToken($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
        $data = $cb->statuses_show_ID(["id" => $id, "tweet_mode" => "extended"]);
        if($data->extended_entities){
            if($data->extended_entities->media[0]->type == 'animated_gif')
            {
                $tmpFile = tmpfile();
                $finalFile = tmpfile();
                fwrite($tmpFile, file_get_contents(end($data->extended_entities->media[0]->video_info->variants)->url));
                exec('ffmpeg -loglevel panic -y -i '.stream_get_meta_data($tmpFile)['uri'].' -f gif '.stream_get_meta_data($finalFile)['uri']);
                fclose($tmpFile);
                $data = fread($finalFile, fstat($finalFile)["size"]);
                fclose($finalFile);
                echo '{"result":true,"type":"gif","data":"'.base64_encode($data).'"}';
                exit();
            }
            if($data->extended_entities->media[0]->type == 'video'){
                $bitrate = 0;
                $finalURL = '';
                foreach($data->extended_entities->media[0]->video_info->variants as $video)
                {
                    if($video->content_type == 'video/mp4' && $video->bitrate > $bitrate)
                    {
                        $bitrate = $video->bitrate;
                        $finalURL = $video->url;
                    }
                }
                echo '{"result":true,"type":"video","url":"'.$finalURL.'"}';
                exit();
            }
        }
        die('{"result":false,"error":"No valid media found"}');
        break;
    case "/callback":
        $dotenv = new Dotenv\Dotenv(__DIR__);
        try
        {
            $dotenv->load();
            $dotenv->required(['CONSUMER_SECRET', 'CONSUMER_KEY']);
        } catch (RuntimeException $e)
        {
            die();
        }
        \Codebird\Codebird::setConsumerKey($_ENV["CONSUMER_KEY"], $_ENV["CONSUMER_SECRET"]);
        $cb = \Codebird\Codebird::getInstance();
        if (isset($_GET['oauth_verifier']) && isset($_SESSION['oauth_verify']))
        {
            $cb->setToken($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
            unset($_SESSION['oauth_verify']);
            $reply = $cb->oauth_accessToken([
                'oauth_verifier' => $_GET['oauth_verifier']
            ]);
            $_SESSION['oauth_token'] = $reply->oauth_token;
            $_SESSION['oauth_token_secret'] = $reply->oauth_token_secret;
            $_SESSION['auth'] = true;
            header('Location: /');
        }
        break;
    default:
        $dotenv = new Dotenv\Dotenv(__DIR__);
        try
        {
            $dotenv->load();
            $dotenv->required(['CONSUMER_KEY', 'CONSUMER_SECRET']);
        } catch (RuntimeException $e){
            die();
        }
        \Codebird\Codebird::setConsumerKey($_ENV["CONSUMER_KEY"], $_ENV["CONSUMER_SECRET"]);
        $cb = \Codebird\Codebird::getInstance();
        exec('git rev-parse --verify HEAD', $hash);
        $hash = substr($hash[0], 0, 7)
?>
    <!DOCTYPE HTML>
    <html>
        <head>
            <title>Twitter Media Download</title>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
            <link href="http://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
            <link rel="stylesheet" href="/assets/css/materialize.min.css">
            <link rel="stylesheet" href="/assets/css/jquery.fullPage.min.css">
            <link rel="stylesheet" href="/assets/css/main.css">
        </head>
        <body class="blue darken-2 grey-text lighten-2">
            <div id="loader" class="valign-wrapper">
                <div class="preloader-wrapper big active valign"><div class="spinner-layer spinner-blue"><div class="circle-clipper left"><div class="circle"></div></div><div class="gap-patch"> <div class="circle"></div> </div><div class="circle-clipper right"> <div class="circle"></div> </div> </div> <div class="spinner-layer spinner-red"> <div class="circle-clipper left"><div class="circle"></div> </div><div class="gap-patch"> <div class="circle"></div> </div><div class="circle-clipper right"> <div class="circle"></div> </div> </div> <div class="spinner-layer spinner-yellow"> <div class="circle-clipper left"> <div class="circle"></div> </div><div class="gap-patch"> <div class="circle"></div> </div><div class="circle-clipper right"> <div class="circle"></div> </div> </div> <div class="spinner-layer spinner-green"> <div class="circle-clipper left"> <div class="circle"></div> </div><div class="gap-patch"> <div class="circle"></div> </div><div class="circle-clipper right"> <div class="circle"></div> </div> </div> </div>
            </div>
            <div id="fullpage">
                <div class="section active" id="main">
                    <div class="valign-wrapper center-align">
                        <div class="valign" id="main_content">
                            <h1>Twitter Media Download</h1>
                            <p id="error_msg" class="red-text lighten-1"></p>
                            <br>
                            <?php if(isset($_SESSION['auth']) && $_SESSION['auth']): ?>
                            <div id="content">
                                <div class="input-field col s6">
                                    <input placeholder="f.e. https://twitter.com/therealskildust/status/803683096616960002" id="url" type="text" class="validate">
                                    <label for="first_name">Status URL</label>
                                </div>
                                <button class="btn waves-effect waves-light blue lighten-2" type="submit" name="action" id="submit">Submit
                                    <i class="material-icons right">send</i>
                                </button>
                            </div>
                            <div id="preloader" style="display:none">
                                    <div class="preloader-wrapper big active"><div class="spinner-layer spinner-blue"><div class="circle-clipper left"><div class="circle"></div></div><div class="gap-patch"> <div class="circle"></div> </div><div class="circle-clipper right"> <div class="circle"></div> </div> </div> <div class="spinner-layer spinner-red"> <div class="circle-clipper left"><div class="circle"></div> </div><div class="gap-patch"> <div class="circle"></div> </div><div class="circle-clipper right"> <div class="circle"></div> </div> </div> <div class="spinner-layer spinner-yellow"> <div class="circle-clipper left"> <div class="circle"></div> </div><div class="gap-patch"> <div class="circle"></div> </div><div class="circle-clipper right"> <div class="circle"></div> </div> </div> <div class="spinner-layer spinner-green"> <div class="circle-clipper left"> <div class="circle"></div> </div><div class="gap-patch"> <div class="circle"></div> </div><div class="circle-clipper right"> <div class="circle"></div> </div> </div> </div>
                            </div>
                            <div id="complete" style="display:none">
                                <div id="c_content"></div><br>
                                <a class="btn waves-effect waves-light blue lighten-2" href="" download>Download</a>
                            </div>
                            <?php else:
                                $cb->setUseCurl(true);
                                $reply = $cb->oauth_requestToken([
                                    'oauth_callback' => 'http://' . $_SERVER['HTTP_HOST'] . '/callback'
                                ]);
                                error_log('http://' . $_SERVER['HTTP_HOST'] . '/callback');
                                if($reply->httpstatus === 200):
                                    $_SESSION['oauth_token'] = $reply->oauth_token;
                                    $_SESSION['oauth_token_secret'] = $reply->oauth_token_secret;
                                    $_SESSION['oauth_verify'] = true;
                                    $cb->setToken($reply->oauth_token, $reply->oauth_token_secret);
                                    $auth_url = $cb->oauth_authorize(); ?>
                            <a class="btn waves-effect waves-light blue lighten-2" href="<?php echo $auth_url; ?>">Login with Twitter</a>
                            <?php else:
                                    error_log(json_encode($reply))?>
                                    <p class="red-text" id="error">Something went wrong :( <br>Try again later!</p>
                            <?php endif;endif; ?>
                        </div>
                    </div>
                </div>
                <div class="section" id="info">
                    <div class="valign-wrapper center-align">
                        <div class="valign">
                           <div class="container">
                               <p>
                                   <b>Twitter Media Downloader</b> is a simple side project of mine.<br>
                                   It brings the convenience of having your favorite Twitter-Content at your fingertips.
                                   I mean, when was the last time you saw a cute video or gif on Twtitter and thought "Gee, if only I could download this now to show it to my friends later."
                               </p>
                               <div class="divider"></div>
                               <p>
                                   Created ©2016-2017 by <a target="_blank" href="https://skildust.com" class="pink-text">sKiLdUsT</a>
                               </p>
                               <p>
                                   <b>Version 1.0 (<?php echo $hash ?>)</b>
                               </p>
                           </div>
                        </div>
                    </div>
                </div>
            </div>
            <script src="/assets/js/jquery.min.js" type="text/javascript"></script>
            <script src="/assets/js/materialize.min.js" type="text/javascript"></script>
            <script src="/assets/js/jquery.fullPage.min.js" type="text/javascript"></script>
            <script src="/assets/js/main.js" type="text/javascript"></script>
        </body>
    </html>
<?php
        break;
}
?>