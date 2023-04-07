<?
namespace Jamilco\Main;

class Progress
{
    protected $total;
    protected $index;
    protected $start;

    public function __construct($total = 0)
    {
        $this->total = $total;
        $this->index = 0;
        $this->start = microtime(true);
    }

    public function step()
    {
        $this->index++;
        $columns = getenv('COLUMNS');
        //if ($this->index % 100 === 0)
        $speed = floor($this->index / (microtime(true) - $this->start));
        $line = "index: ".$this->index."/".$this->total
            ." speed: $speed items/s remain: ".floor(($this->total - $this->index) / ($speed ? $speed : 0.0001) / 60)."m"
            ." mem: ".floor(memory_get_usage() / 1024 / 1024).'M';
        echo "\r".str_pad($line, $columns);
    }
}