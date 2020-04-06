package main

import (
	"encoding/json"
	"io"
	"log"
	"net"
	"strconv"
	"strings"
)

func sendMessage(wf io.Writer, conn *net.UDPConn, cacheName string, key string, m map[string]interface{}) error {
	v, err := json.Marshal(m)
	if err != nil {
		return err
	}
	s := strings.Replace(cacheName+"_"+key, "|", "_", -1) + "|" + string(v)
	udpSend(conn, s)
	return writeLine(wf, "+OK")
}

func writeBack(handlers map[string]handler, config *asSetConfig, ctx *context) map[string]handler {
	if config.WriteBackTarget == "" {
		return handlers
	}
	ra, err := net.ResolveUDPAddr("udp", config.WriteBackTarget)
	if err != nil {
		panic(err)
	}
	conn, err := net.DialUDP("udp", nil, ra)
	if err != nil {
		panic(err)
	}
	if config.WriteBackSetTimeout {
		cacheName := "CACHE_" + strings.ToUpper(ctx.set)
		m := make(map[string]interface{})
		m["cache_name"] = cacheName
		m["method"] = "setTimeout"
		a := make([]interface{}, 2)
		m["args"] = a
		log.Printf("%s: Using write back for setTimeout to %s", ctx.set, config.WriteBackTarget)
		f := func(wf io.Writer, ctx *context, args [][]byte) error {
			key := string(args[0])
			ttl, err := strconv.Atoi(string(args[1]))
			if err != nil {
				return err
			}
			a[0] = key
			a[1] = ttl
			return sendMessage(wf, conn, cacheName, key, m)
		}
		handlers["EXPIRE"] = handler{handlers["EXPIRE"].argsCount, handlers["EXPIRE"].argsLogCount, f, true}
	}
	if config.WriteBackHincrBy {
		cacheName := "CACHE_" + strings.ToUpper(ctx.set)
		m := make(map[string]interface{})
		m["cache_name"] = cacheName
		m["method"] = "hIncrBy"
		a := make([]interface{}, 3)
		m["args"] = a
		log.Printf("%s: Using write back for hIncrBy to %s", ctx.set, config.WriteBackTarget)
		f := func(wf io.Writer, ctx *context, args [][]byte) error {
			key := string(args[0])
			field := string(args[1])
			incr, err := strconv.Atoi(string(args[2]))
			if err != nil {
				return err
			}
			a[0] = key
			a[1] = field
			a[2] = incr
			return sendMessage(wf, conn, cacheName, key, m)
		}
		handlers["HINCRBY"] = handler{handlers["HINCRBY"].argsCount, handlers["HINCRBY"].argsLogCount, f, true}
	}
	return handlers

}
