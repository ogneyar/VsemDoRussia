<?php

use yii\helpers\Html;

use app\commands\PurchaseNotificationController;

$this->title = 'Тест';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="class">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('ТЕЕЕст', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php

        echo("<br/>");

        // echo(PERCENT_FOR_ALL);
        
        echo($percent_for_all);
        
        echo("<br/>");
        
        echo($constants["PERCENT_FOR_ALL"]);

        echo("<br/>");

        foreach($accounts as $account)
        {
            // var_dump($account);
            foreach($account as $acc)
            {
                echo("<br />");
                echo date("h");
                echo("<br />");

                echo("user_id: ");
                echo($acc->user_id);
                echo(" - ");
                echo("type: ");
                echo($acc->type);
                echo(" - ");
                echo("total: ");
                echo($acc->total);
                echo(" - ");

                // if ($acc->type == "deposit")

                // email - Списание членсого взноса

                
                foreach($users as $user) {
                    if ($user->id == $acc->user_id) var_dump($user->email);
                }
            }
            echo("<br/>");

            // echo("<br/>");
        }
    ?>

</div>