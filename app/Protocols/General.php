<?php

namespace App\Protocols;


use App\Utils\Helper;

class General
{
    public $flag = 'general';
    private $servers;
    private $user;

    public function __construct($user, $servers)
    {
        $this->user = $user;
        $this->servers = $servers;
    }

    public function handle()
    {
        $servers = $this->servers;
        $user = $this->user;
        $uri = '';

        foreach ($servers as $item) {
            if ($item['type'] === 'vmess') {
                $uri .= self::buildVmess($user['uuid'], $item);
            }
            if ($item['type'] === 'vless') {
                $uri .= self::buildVless($user['uuid'], $item);
            }
            if ($item['type'] === 'shadowsocks') {
                $uri .= self::buildShadowsocks($item['password'], $item);
            }
            if ($item['type'] === 'trojan') {
                $uri .= self::buildTrojan($user['uuid'], $item);
            }
            if ($item['type'] === 'hysteria') {
                $uri .= self::buildHysteria($user['uuid'], $item);
            }
        }
        return base64_encode($uri);
    }

    public static function buildShadowsocks($password, $server)
    {
        $name = rawurlencode($server['name']);
        $str = str_replace(
            ['+', '/', '='],
            ['-', '_', ''],
            base64_encode("{$server['cipher']}:{$password}")
        );
        return "ss://{$str}@{$server['host']}:{$server['port']}#{$name}\r\n";
    }

    public static function buildVmess($uuid, $server)
    {
        $config = [
            "v" => "2",
            "ps" => $server['name'],
            "add" => $server['host'],
            "port" => (string)$server['port'],
            "id" => $uuid,
            "aid" => '0',
            "net" => $server['network'],
            "type" => "none",
            "host" => "",
            "path" => "",
            "tls" => $server['tls'] ? "tls" : "",
        ];
        if ($server['tls']) {
            if ($server['tlsSettings']) {
                $tlsSettings = $server['tlsSettings'];
                if (isset($tlsSettings['serverName']) && !empty($tlsSettings['serverName']))
                    $config['sni'] = $tlsSettings['serverName'];
            }
        }
        if ((string)$server['network'] === 'tcp') {
            $tcpSettings = $server['networkSettings'];
            if (isset($tcpSettings['header']['type'])) $config['type'] = $tcpSettings['header']['type'];
            if (isset($tcpSettings['header']['request']['path'][0])){
                $paths = $tcpSettings['header']['request']['path'];
                $config['path'] = $paths[array_rand($paths)];
            }
            if (isset($tcpSettings['header']['request']['headers']['Host'][0])){
                $hosts = $tcpSettings['header']['request']['headers']['Host'];
                $config['host'] = $hosts[array_rand($hosts)];
            }
        }
        if ((string)$server['network'] === 'ws') {
            $wsSettings = $server['networkSettings'];
            if (isset($wsSettings['path'])) $config['path'] = $wsSettings['path'];
            if (isset($wsSettings['headers']['Host'])) $config['host'] = $wsSettings['headers']['Host'];
        }
        if ((string)$server['network'] === 'grpc') {
            $grpcSettings = $server['networkSettings'];
            if (isset($grpcSettings['serviceName'])) $config['path'] = $grpcSettings['serviceName'];
        }
        return "vmess://" . base64_encode(json_encode($config)) . "\r\n";
    }

    public static function buildVless($uuid, $server){
        $host = $server['host']; //节点地址
        $port = $server['port']; //节点端口
        $name = $server['name']; //节点名称

        $config = [
            'mode' => 'multi', //grpc传输模式
            'security' => '', //传输层安全 tls/reality
            'encryption' => 'none', //加密方式
            'type' => $server['network'], //传输协议
        ];
        // 判断是否开启XTLS
        if($server['flow']) ($config['flow'] = $server['flow']);
        // 如果开启TLS
        if ($server['tls']) {
            switch($server['tls']){
                case 1:
                    if ($server['tls_settings']) {
                        $tlsSettings = $server['tls_settings'];
                        if (isset($tlsSettings['server_name']) && !empty($tlsSettings['server_name']))
                            $config['sni'] = $tlsSettings['server_name'];
                            $config['security'] = "tls";
                    }
                    break;
                case 2: //reality
                    $config['security'] = "reality";
                    $tls_settings = $server['tls_settings'];
                    if(($tls_settings['public_key'] ?? null)
                    && ($tls_settings['short_id'] ?? null)
                    && ($tls_settings['server_name'] ?? null)){
                        $config['pbk'] = $tls_settings['public_key'];
                        $config['sid'] = $tls_settings['short_id'];
                        $config['sni'] = $tls_settings['server_name'];
                        $config['servername'] = $tls_settings['server_name'];
                        $config['spx'] = "/";
                        $fingerprints = ['chrome', 'firefox', 'safari', 'ios', 'edge', 'qq']; //随机客户端指纹
                        $config['fp'] = $fingerprints[rand(0,count($fingerprints) - 1)];
                    };
                    break;
            }
        }
        // 如果传输协议为ws
        if ((string)$server['network'] === 'ws') {
            $wsSettings = $server['network_settings'];
            if (isset($wsSettings['path'])) $config['path'] = $wsSettings['path'];
            if (isset($wsSettings['headers']['Host'])) $config['host'] = $wsSettings['headers']['Host'];
        }
        // 传输协议为grpc
        if ((string)$server['network'] === 'grpc') {
            $grpcSettings = $server['network_settings'];
            if (isset($grpcSettings['serviceName'])) $config['serviceName'] = $grpcSettings['serviceName'];
        }

        $user = $uuid . '@' . $host . ':' . $port;
        $query = http_build_query($config);
        $fragment = urlencode($name);
        $link = sprintf("vless://%s?%s#%s\r\n", $user, $query, $fragment);
        return $link;
    }

    public static function buildTrojan($password, $server)
    {
        $name = rawurlencode($server['name']);
        $query = http_build_query([
            'allowInsecure' => $server['allow_insecure'],
            'peer' => $server['server_name'],
            'sni' => $server['server_name']
        ]);
        $uri = "trojan://{$password}@{$server['host']}:{$server['port']}?{$query}#{$name}";
        $uri .= "\r\n";
        return $uri;
    }

    public static function buildHysteria($password, $server)
    {
        $params = [];
        // Return empty if version is not 2
        if ($server['version'] !== 2) {
            return '';
        }

        if ($server['server_name']) {
            $params['sni'] = $server['server_name'];
            $params['security'] = 'tls';
        }

        if ($server['is_obfs']) {
            $params['obfs'] = 'salamander';
            $params['obfs-password'] = $server['server_key'];
        }

        $params['insecure'] = $server['insecure'] ? 1 : 0;

        $query = http_build_query($params);
        $name = rawurlencode($server['name']);

        $uri = "hysteria2://{$password}@{$server['host']}:{$server['port']}?{$query}#{$name}";
        $uri .= "\r\n";

        return $uri;
    }

}
