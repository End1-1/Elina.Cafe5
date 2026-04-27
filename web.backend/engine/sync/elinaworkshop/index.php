<?php
require_once __DIR__ . "/elinaworkshop.php";

class IndexPhp extends DB
{
    public function getListOfTask()
    {
        $sql = <<<EOD
        select t.f_id, t.f_product, concat(t.f_id, ' ', p.f_name) as f_name from mf_tasks t 
        left join mf_actions_group p on p.f_id=t.f_product   
        where t.f_state=1
        EOD;
        $data = $this->stmtall($sql)->fetch_all(MYSQLI_ASSOC);
        $this->result["data"] = $data;
        $this->echoResult();
    }
    public function getListOfTeamLead()
    {
        $sql = <<<EOD
        select distinct(u.f_teamlead) as f_id ,
        concat_ws(' ', u.f_last, u.f_first) as f_name 
        from s_user m 
        inner join s_user u on u.f_teamlead=m.f_id 
        WHERE u.f_teamlead>0  
        order by 2
        EOD;
        $data = $this->stmtall($sql)->fetch_all(MYSQLI_ASSOC);
        $this->result["data"] = $data;
        $this->echoResult();
    }
    public function getListOfEmployee()
    {
        $sql = <<<EOD
        select u.f_id, u.f_teamlead, concat(u.f_last, ' ', u.f_first) as f_name  
        from s_user u 
        WHERE u.f_teamlead>0 and f_state=1 
        order by 3 
        EOD;
        $this->result["data"] = $this->stmtall($sql)->fetch_all(MYSQLI_ASSOC);
        $this->echoResult();
    }
    public function getListOfWorks()
    {
        $where = "where p.f_date='{$this->params->f_date}' ";
        $where .= " and p.f_worker={$this->params->f_worker} ";
        if ($this->params->f_task > 0) {
            $where .= " and t.f_id={$this->params->f_task} ";
        }
        if ($this->params->f_teamlead > 0) {
            $where .= " and t.f_responsible={$this->params->f_teamlead} ";
        }

        $sql = <<<EOD
        select p.f_id, pr.f_name as f_productname, ac.f_name as f_processname, 
        p.f_qty, p.f_price, p.f_taskid, concat('#', t.f_id, ' ', pr.f_name) as f_taskdate, 
        t.f_qty as f_goal, cast( dp.f_qty as unsigned) as f_ready, p.f_process, p.f_laststep 
        from mf_daily_process p 
        inner join mf_actions_group pr on pr.f_id=p.f_product 
        inner join mf_actions ac on ac.f_id=p.f_process 
        left join mf_tasks t on t.f_id=p.f_taskid 
        left join (select dp.f_process, dp.f_taskid, sum(dp.f_qty) as f_qty 
        from mf_daily_process dp group by 1,2) as dp on dp.f_taskid=p.f_taskid and dp.f_process=p.f_process 
        $where
        order by pr.f_name, ac.f_id
        EOD;
        $this->result["data"] = $this->stmtall($sql)->fetch_all(MYSQLI_ASSOC);
        $this->echoResult();
    }
    public function listOfTaskWorks()
    {
        $sql = <<<EOD
        select p.f_id , p.f_rowid + 1 as f_rowid , p.f_product , gr.f_name as f_grname, 
        p.f_process , ac.f_name as f_acname, 
        p.f_durationsec , p.f_price , coalesce(ac.f_state  , 0) as f_state
        from mf_process p 
        inner join mf_actions_group gr on gr.f_id=p.f_product 
        inner join mf_actions ac on ac.f_id=p.f_process 
        where p.f_product = {$this->params->f_product} 
        EOD;
        $this->result["data"] = $this->stmtall($sql)->fetch_all(MYSQLI_ASSOC);
        $this->echoResult();
    }
    public function addWorkToTask()
    {
        $this->addWorkerToDay(false);

        $v["f_date"] = $this->params->f_date;
        $v["f_worker"] = $this->params->f_worker;
        $v["f_product"] = $this->params->f_product;
        $v["f_process"] = $this->params->f_process;
        $v["f_qty"] = 0;
        $v["f_price"] = $this->params->f_price;
        $v["f_taskid"] = $this->params->f_taskid;
        $v["f_laststep"] = $this->params->f_laststep;
        $this->sinsert("mf_daily_process", $v);
        $this->result["data"] = [];
        $this->echoResult();
    }
    public function employesOfTheDay()
    {
        $where = "where f_date='{$this->params->f_date}' ";
        if ($this->params->f_teamlead > 0) {
            $where .= " and u.f_teamlead={$this->params->f_teamlead} ";
        }

        $sql = <<<EOD
        select m.f_worker as f_id, u.f_teamlead, concat(u.f_last, ' ', u.f_first) as f_name 
        from mf_daily_workers m 
        inner join s_user u on u.f_id=m.f_worker $where
        EOD;
        $data = $this->stmtall($sql)->fetch_all(MYSQLI_ASSOC);
        $this->result["data"] = $data;
        if (!empty($this->params->debug)) {
        $this->result["sql"] = str_replace("\n", " ", str_replace("\r", " ", $sql));
        }
        $this->echoResult();
    }
    public function addWorkerToDay($echoresult = true)
    {
        $sqlcheck = <<<EOD
        select f_id from mf_daily_workers where f_date='{$this->params->f_date}' and f_worker={$this->params->f_worker}
        EOD;
        if (empty($this->stmtall($sqlcheck))) {
            $v["f_date"] = $this->params->f_date;
            $v["f_worker"] = $this->params->f_worker;
            $this->sinsert("mf_daily_workers", $v);
        }
        if ($echoresult) {
            $this->result["data"] = [];
            $this->echoResult();
        }
    }
}

if (!empty($params->query)) {
    $ddbb = new IndexPhp();
    switch ($params->query) {
        case 1:
            $ddbb->getListOfTask();
            break;
        case 2:
            $ddbb->getListOfTeamLead();
            break;
        case 3:
            $ddbb->getListOfEmployee();
            break;
        case 4:
            $ddbb->getListOfWorks();
            break;
        case 5:
            $ddbb->listOfTaskWorks();
            break;
        case 6:
            $ddbb->addWorkToTask();
            break;
        case 7:
            $ddbb->employesOfTheDay();
            break;
        case 8:
            $ddbb->addWorkerToDay();
            break;
    }
}