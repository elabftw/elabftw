// This program will listen on a socket and send commands to bin/console using the php with env
// © 2025 Nicolas CARPi
// License: AGPLv3
package main

import (
	"bufio"
	"context"
	"fmt"
	"log"
	"net"
	"os"
	"os/exec"
	"os/signal"
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
	socketPath := "/run/invoker/invoker.sock"

	// Remove the socket if it already exists
	if err := os.RemoveAll(socketPath); err != nil {
		fmt.Println("error removing existing socket:", err)
		return
	}

	// Listen on the Unix socket
	listener, err := net.Listen("unix", socketPath)
	if err != nil {
		fmt.Println("error listening on Unix socket:", err)
		return
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

func handleConnection(conn net.Conn, mu *sync.Mutex, psk string) {
	defer conn.Close()
	scanner := bufio.NewScanner(conn)

	for scanner.Scan() {
		cmdStr := scanner.Text()

		// Extract the PSK from the message
		parts := strings.SplitN(cmdStr, "|", 2)
		if len(parts) != 2 || parts[0] != psk {
			log.Println("invalid or missing PSK")
			continue
		}

		// Extract the actual command
		actualCmd := parts[1]
		log.Printf("received command: %s", actualCmd)

		// track the time it takes to run the command, so start a timer
		start := time.Now()
		// we only want to process one command at the time
		mu.Lock()
		// split received command into arguments before passing it to exec.Command
		args := strings.Fields(actualCmd)
		cmd := exec.Command("/usr/bin/php", append([]string{"/elabftw/bin/console"}, args...)...)
		output, err := cmd.CombinedOutput()
		// stop timer
		elapsed := time.Since(start)
		elapsedFormatted := fmt.Sprintf("%dm%02ds", int(elapsed.Minutes()), int(elapsed.Seconds())%60)
		log.Printf("finished processing: %s in %s", actualCmd, elapsedFormatted)
		mu.Unlock()

		if err != nil {
			fmt.Fprintf(conn, "error executing command: %v\n", err)
		} else {
			fmt.Fprintf(conn, "output:\n%s\n", output)
		}
	}

	if err := scanner.Err(); err != nil {
		fmt.Println("error reading from connection:", err)
	}
}
