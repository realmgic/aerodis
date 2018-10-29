package main

import (
	"io/ioutil"
	"log"
	"time"

	"github.com/BurntSushi/toml"
)

type asSetConfig struct {
	Proto               string `toml:"proto"`
	Listen              string `toml:"listen"`
	Set                 string `toml:"set"`
	ExpandedMap         bool   `toml:"expanded_map"`
	ExpandedMapTTL      int    `toml:"expanded_map_ttl"`
	CacheSize           int    `toml:"cache_size"`
	CacheTTL            int    `toml:"cache_ttl"`
	LogCommands         bool   `toml:"log_commands"`
	Statsd              string `toml:"statsd"`
	WriteBackTarget     string `toml:"write_back_target"`
	WriteBackSetTimeout bool   `toml:"write_back_setTimeout"`
	WriteBackHincrBy    bool   `toml:"write_back_hIncrBy"`
}

type proxyConfig struct {
	AsHostList            []string      `toml:"as_host_list"`
	AsPort                int           `toml:"as_port"`
	AsSetList             []asSetConfig `toml:"sets"`
	AsReadSocketTimeout   time.Duration `toml:"as_read_socket_timeout"`
	AsWriteSocketTimeout  time.Duration `toml:"as_write_socket_timeout"`
	AsLogLevel            string        `toml:"as_log_level"`
	AsConnectionQueueSize int           `toml:"as_connection_queue_size"`
	MaxFDs                int           `toml:"max_fds"`
	Statsd                string        `toml:"statsd"`
}

var ProxyConfig = &proxyConfig{}

func loadProxyConfig(configFile string) {
	configBytes, err := ioutil.ReadFile(configFile)
	if err != nil {
		panic(err)
	}
	if _, err := toml.Decode(string(configBytes), ProxyConfig); err != nil {
		panic(err)
	}
	log.Printf("%#v\n", ProxyConfig)
}
