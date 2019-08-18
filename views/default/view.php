<?php


?>
<div id="content-box" class="content">
    <div class="x-right">
        <img style="vertical-align:top;" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAACAklEQVRYR+2Wy6uOURTGf08uucwMlIkMTaUM3EO5dEiKdFBEpmTCiAElGZidMpGExBG5ZKCMGJiYmBib+BuUerRqv7X7er9v7/0edQy+t/bo3e3nt56111pbLPKnRdZnCvB/OmB7CbBW0q9/cUdsr5f0s++sXgdszwL3gX2Svi4EwvZt4AqwWtKf0bPGASwDXgG7Ykn6NgQiiV8Fzkp6VO1AbLS9FHgNbAf2tEJk4rOSno0LYOIlHIHYIel7jRO2rwOxTk8Sj7OKVZAgngO7UzomQti+BtwCjkuKNE78igApHVEVL0oQSfwmcKJGvMqBDj+V5mNgP7BV0o88NNuXgbvAUUnvS5F3/6scyCBi/1NgL7Czg0jid4BjLeJNDoxAPAQOBgRwAAjxGUkfayMf5EAPxBFgJXB4iPggBzKIB8A54DdwSNKn1ugHA9ieAy4Cp4Bo23Exo21/boVouoSpJDvxk5LmU594GQAB0grRBGD7HnAp1fl8lo5o2x1EtO3qAVYNkImfkfRk1OrkxDtgW8sUrQIoiWdOLAfetEAUAbKp1ht5jxMB8QHYUjPKS9MwHhMxzy9IirKr+myvAKIdby6N8rEAtm8AsZrEs3RUQYx7Ec0Ab4HzLZH3pCMgoj1vBNa1PMnWABtaX0F9+bG9Ctgk6Uvf/+IlrEr6AjZNAaYO/AV3mccheExXNgAAAABJRU5ErkJggg==">
    </div>
    <div class="content-tips">
        <ul>
            <li>
                <input id="tab1" type="radio" name="tab" checked>
                <label for="tab1" class="tab1">基本</label>
                <div class="content">
                    <div>1.请求信息：<?=$info['base_info']['request_info']?></div>
                    <div>2.运行时间：<?=$info['base_info']['run_time']?></div>
                    <div>3.内存开销：<?=$info['base_info']['memory']?></div>
                    <div>4.文件加载数：<?=$info['base_info']['file_require_count']?></div>
                    <div>5.会话信息：<?=$info['base_info']['session_info']?></div>
                    <div>6.当前IP：<?=$info['base_info']['ip']?></div>
                </div>
            </li>
            <li>
                <input id="tab2" type="radio" name="tab">
                <label for="tab2" class="tab2">SQL</label>

                <div class="content">

                    <div style="display: inline">查询：<?=$info['base_info']['sql_query_count']?>个；</div>
                    <div style="display: inline">增、删、改：<?=$info['base_info']['sql_execute_count']?>个</div>

                    <?php foreach($info['sql_info'] as $key =>  $item):?>
                        <div><?=($key + 1)?>、<?=$item['sql']?>&nbsp;[时间：<?=$item['time']?>][所在文件：<?=$item['file']?>&nbsp;&nbsp;第<?=$item['line']?>行]</div>
                    <?php endforeach;?>
                </div>

            </li>
            <li>
                <input id="tab3" type="radio" name="tab">
                <label for="tab3" class="tab3">其他</label>
                <div class="content">
                    <div>数据库：<?=$info['base_info']['driver_name']?> &nbsp;版本号：<?=$info['base_info']['db_version']?></div>
                    <div>数据库表：<?=$info['base_info']['table_num']?>张</div>
                    <div>服务器操作系统：</div>
                    <div>服务器引擎信息：<?=$info['base_info']['server_info']?></div>
                    <div>CPU型号[4核]：Intel(R) Xeon(R) CPU E5-2697 v4 @ 2.30GHz | 频率:2299.996</div>
                    <div>CPU信息：15%us, 17%sy, 0%ni, 68%id, 0%wa, 0%irq, 0%softirq </div>
                    <div>硬盘信息：总空间 <?=$info['base_info']['disk_info']['dt']?> G， 已用 <?=$info['base_info']['disk_info']['df']?> G， 空闲 <?=$info['base_info']['disk_info']['du']?> G， 使用率 <?=$info['base_info']['disk_info']['hdPercent']?>%</div>
                    <div>内存使用状况：物理内存：共 7.775 G , 已用 6.207 G , 空闲 1.569 G , 使用率 79.98%</div>
                    <div>PHP已安装的模块：
                        <?php
                            $able=get_loaded_extensions();
                            foreach ($able as $key=>$value) {
                                echo "$value&nbsp;&nbsp;";
                            }
                        ?>
                    </div>

                </div>
            </li>
        </ul>
    </div>
</div>
