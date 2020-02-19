<?php
namespace bingbing;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\utils\Config;
use pocketmine\item\Item;
use pocketmine\command\CommandSender;


class BingBingPacking extends PluginBase implements Listener{
    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        @mkdir($this->getDataFolder());

        $this->database = new Config($this->getDataFolder()."data.yml", Config::YAML, []);
        $this->lists = new Config($this->getDataFolder()."list.yml", Config::YAML , []);
        $this->items = new Config($this->getDataFolder()."config.yml", Config::YAML,[339 , 0]);
        $this->item = $this->items->getAll();
        $this->db = $this->database->getAll();
        $this->list = $this->lists->getAll();
        
    }
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args):bool{
        
        $item =  $sender->getInventory()->getItemInHand();
        if ($command == "포장") {
            if (isset($args[0])) {
                switch ($args[0]){
                    case "생성":
                        if (isset($args[1])){
                            $this->addPacking($sender , $args[1], $item);

                        }
                        return true;
                        break;
                    case "제거":
                        if (isset($args[1])){
                            $this->removePacking($sender, $args[1]);

                        }
                        return true;
                        
                        break;
                    case "목록": 
                        $this->sendPackingList($sender);
                        return true;
                        break;
                    case "템추가":
                        if (isset($args[1])){
                            $this->addItemInPackage($sender, $args[1], $item);

                        }
                        return true;
                        break;
                    case "템목록":
                        if (isset($args[1])){
                            $this->sendItemListToPlayer($sender, $args[1]);
                        }
                        return true;
                        break;
                    case "템제거":
                        if (isset($args[1]) && isset($args[2]) && is_numeric($args[2])){
                            $this->removeItemInPackage($sender, $args[1], $args[2]);
                        }
                        return true;
                        break;
                    case "받기":
                        if (isset($args[1])){
                             $this->recievePackage($sender, $args[1]);
                        }
                       
                        return true;
                        break;
                    default:
                        $sender->sendMessage("/포장 생성/제거 [이름 ] \n /포장 목록 \n /포장 템추가 [이름 ] \n /포장 템목록 \n /포장 템제거 번호 \n 포장 받기 [이름 ]  ");
                        return true;
                        break;
                }
            }
            else {
                $sender->sendMessage("/포장 생성/제거 [이름 ] \n /포장 목록 \n /포장 템추가 [이름 ] \n /포장 템목록 \n /포장 템제거 번호 \n 포장 받기 [이름 ]  ");
                return true;
                
            }
        }
    }
    public function addPacking (Player $player , $name , Item $item) :void{
        if (isset ($this->db[$name])) {
            $player->sendMessage("이미 있는 포장지입니다 ");
            
        }
        else {
            $item->setCustomName($name);
            $item->setCount(1);
            $player->getInventory()->setItemInHand($item);
            $this->db[$name]= [];
            $player->sendMessage("생성 완료  ");
            array_push($this->list , $name );
            
        }
    }
    public function removePacking( Player $player , $name ) :void{
        if (!isset ($this->db[$name])) {
            $player->sendMessage("없는 포장지입니다");
        }
        else {

            foreach ($this->list as $key => $value){
                if ($value == $name){
                    unset($this->list[ $key] );
                    $this->list = array_values($this->list);
                    $player->sendMessage("파괴 완료  ");
                    unset ($this->db[$name] );
                    $this->db = array_values($this->db);
                }
            }
        }
    }
    public function sendPackingList (Player $player):void{
        foreach ($this->list as $list){
            $player->sendMessage("포장지 이름 : ". $list );
        }
    }
    public function addItemInPackage(Player $player , $name ,Item $item) : void{
        if (!isset ($this->db[$name])) {
            $player->sendMessage("없는 포장지입니다 ");
        }
        else {
            array_push($this->db[$name] ,[ $item->getId() , $item->getDamage() , $item->getCount() ,$item->getCustomName() != "" ?$item->getCustomName() : $item->getName() ,$item->getNamedTag() ]);
            $player->sendMessage("등록완료 ");
            $player->getInventory()->setItemInHand(new Item(0));
        }
    }
    public function sendItemListToPlayer(Player $player , $name) : void{
        if (!isset ($this->db[$name])) {
            $player->sendMessage("없는 포장지입니다 ");
            
        }
        else {
            foreach ($this->db[$name] as $list){
                    $player->sendMessage("아이템  : ".$list[3]." 갯수 : ".$list[2]." 개");
                
            }
            
        }
    }
    public function removeItemInPackage (Player $player , $name , int $number): void{
        if ($this->is_exist($name) && isset($this->db[$name][$number-1])){
            unset($this->db[$name][$number-1]);
            $this->db[$name] = array_values($this->db[$name]);
            $player->sendMessage("제거 완료");
        }
        else {
            $player->sendMessage("없는 포장지이거나 없는 아이템입니다. ");
        }
    }
    public function is_exist($name) : bool{
        if (!isset ($this->db[$name])) {
            return false ; // ����
            
        }
        else {
            return true;
        }
    }
    public function recievePackage(Player $player , $name){
        $item = new Item($this->item[0] ,$this->item[1]);
        $item->setCustomName($name);
        $player->getInventory()->addItem($item);
    }
    public function touch(PlayerInteractEvent$event) {
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();
        if ($event->getBlock()->getId() == 120){
            
            foreach ($this->list as $list){
                if ($list == $item->getCustomName()){
                    foreach ($this->db[$list] as $itemdb){
                        $items = new Item($itemdb[0]);
                        $items->setDamage($itemdb[1]);
                        $items->setCount($itemdb[2]);
                        $items->setCustomName($itemdb[3]);
                        $items->setCompoundTag($itemdb[4]);
                        $player->getInventory()->addItem($items);
                        $player->sendMessage("아이템  ".$itemdb[3]." 갯수 : ".$itemdb[2]." 개 추가 완료 되었습니디.");
                        
                    }
                    $item->setCount($item->getCount() - 1);
                    $player->getInventory()->setItemInHand($item);
                  
                }
            }
        }
    }
    /*public function set(BlockPlaceEvent$event){
        $player = $event->getPlayer();
        $item = $event->getBlock(); 
        
        if (isset($this->list[0])){
            foreach ($this->list as $list){
                if ($list == $item->getCustomName()){
                    $event->setCancelled(true);
                    $player->sendMessage("이 블럭은 포장지로 설치 할 수 없습니다.");
                
                
                }
            }
        }
    }*/
    public function onDisable() {
        $this->lists->setAll($this->list);
        $this->lists->save();
        $this->database->setAll($this->db);
        $this->database->save();
    }
}