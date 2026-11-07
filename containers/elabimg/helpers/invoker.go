// This program will listen on a socket and send commands to bin/console using the php with env
// © 2025 Nicolas CARPi
// License: AGPLv3
package main

import (
	"bufio"
	"context"
	"crypto/subtle"
	"errors"
	"fmt"
	"log"
	"net"
	"os"
	"os/exec"
	"os/signal"
	"strconv"
	"strings"
	"sync"
	"syscall"
	"time"
)

func main() {
	log.SetPrefix("invoker: ")
	log.Println("starting invoker...")
	// this var lets us authenticate that messages come from the php app
	psk := os.Getenv("INVOKER_PSK")
	if psk == "" {
		log.Fatal("INVOKER_PSK is not set")
	}
	socketPath := "/run/elabftw/invoker.sock"

	// Remove the socket if it already exists
	if err := os.RemoveAll(socketPath); err != nil {
		fmt.Println("error removing existing socket:", err)
		return
	}

	// Listen on the Unix socket
	listener, err := listenUnix(socketPath)
	if err != nil {
		log.Fatalf("error listening on Unix socket: %v", err)
	}
	defer listener.Close()

	log.Printf("listening on: %s", socketPath)

	// Create a context that we cancel on SIGINT/SIGTERM
	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	// Listen for shutdown signals
	sigCh := make(chan os.Signal, 1)
	signal.Notify(sigCh, syscall.SIGINT, syscall.SIGTERM)
	go func() {
		<-sigCh
		log.Println("shutdown signal received, terminating…")
		// this will make listener.Accept() unblock with an error
		listener.Close()
		cancel()
	}()

	var wg sync.WaitGroup
	var mu sync.Mutex

	for {
		conn, err := listener.Accept()
		if err != nil {
			// If context is done, this error is expected
			select {
			case <-ctx.Done():
				log.Println("stop accepting connections")
				goto wait
			default:
				log.Printf("error accepting connection: %v", err)
				// small sleep to avoid busy-loop on weird errors
				time.Sleep(100 * time.Millisecond)
				continue
			}
		}

		wg.Add(1)
		go func(c net.Conn) {
			defer wg.Done()
			handleConnection(c, &mu, psk)
		}(conn)
	}

wait:
	// Wait for all in-flight handlers to finish
	wg.Wait()
	log.Println("all connections handled, exiting")

}

func listenUnix(socketPath string) (net.Listener, error) {
	// Restrict access from creation, before chmod can run.
	previousMask := syscall.Umask(0077)
	listener, err := net.Listen("unix", socketPath)
	syscall.Umask(previousMask)
	if err != nil {
		return nil, err
	}
	if err := os.Chmod(socketPath, 0600); err != nil {
		listener.Close()
		return nil, fmt.Errorf("restricting socket permissions: %w", err)
	}
	return listener, nil
}

func parseCommand(message, psk string) ([]string, error) {
	receivedPSK, actualCmd, ok := strings.Cut(message, "|")
	if !ok || psk == "" || subtle.ConstantTimeCompare([]byte(receivedPSK), []byte(psk)) != 1 {
		return nil, errors.New("invalid or missing PSK")
	}

	// Exports are the only tasks currently submitted by the PHP Invoker.
	// Match the complete command and its arguments, never a console prefix.
	args := strings.Fields(actualCmd)
	if len(args) != 2 || args[0] != "export:process" {
		return nil, errors.New("command not allowed")
	}
	id, err := strconv.ParseInt(args[1], 10, 64)
	if err != nil || id <= 0 || strconv.FormatInt(id, 10) != args[1] {
		return nil, errors.New("invalid export ID")
	}
	return args, nil
}

func handleConnection(conn net.Conn, mu *sync.Mutex, psk string) {
	defer conn.Close()
	scanner := bufio.NewScanner(conn)

	for scanner.Scan() {
		args, err := parseCommand(scanner.Text(), psk)
		if err != nil {
			log.Printf("rejected command: %v", err)
			fmt.Fprintln(conn, "error: command rejected")
			return
		}

		actualCmd := strings.Join(args, " ")
		log.Printf("received command: %s", actualCmd)

		// track the time it takes to run the command, so start a timer
		start := time.Now()
		// we only want to process one command at the time
		mu.Lock()
		cmd := exec.Command("/usr/bin/php", append([]string{"/elabftw/bin/console"}, args...)...)
		// Use a working directory allowed by PHP's open_basedir (#7626).
		cmd.Dir = "/elabftw"
		// Console output can contain sensitive data. The PHP client does not use it.
		err = cmd.Run()
		// stop timer
		elapsed := time.Since(start)
		elapsedFormatted := fmt.Sprintf("%dm%02ds", int(elapsed.Minutes()), int(elapsed.Seconds())%60)
		log.Printf("finished processing: %s in %s", actualCmd, elapsedFormatted)
		mu.Unlock()

		if err != nil {
			log.Printf("error executing command %s: %v", actualCmd, err)
			fmt.Fprintln(conn, "error executing command")
		} else {
			fmt.Fprintln(conn, "ok")
		}
	}

	if err := scanner.Err(); err != nil {
		fmt.Println("error reading from connection:", err)
	}
}
