#!/usr/bin/env python3
"""
Gulf Global Co Website Sharing Tool
This script helps you share your website with friends and create PDF presentations.
"""

import os
import webbrowser
import subprocess
import socket
from http.server import HTTPServer, SimpleHTTPRequestHandler
import threading
import time

def get_local_ip():
    """Get the local IP address of this machine"""
    try:
        # Connect to a remote server to get local IP
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))
        ip = s.getsockname()[0]
        s.close()
        return ip
    except:
        return "127.0.0.1"

def start_local_server():
    """Start a local HTTP server"""
    os.chdir('httpdocs')
    server = HTTPServer(('', 8000), SimpleHTTPRequestHandler)
    print("🌐 Starting local server...")
    print(f"📍 Local URL: http://localhost:8000")
    print(f"🌍 Network URL: http://{get_local_ip()}:8000")
    print("📱 Share the Network URL with friends on the same WiFi")
    print("⏹️  Press Ctrl+C to stop the server")
    server.serve_forever()

def open_presentation():
    """Open the presentation HTML file"""
    presentation_path = os.path.join('..', 'Website_Presentation.html')
    if os.path.exists(presentation_path):
        webbrowser.open(f'file://{os.path.abspath(presentation_path)}')
        print("📄 Presentation opened in browser")
        print("💡 To create PDF: Print the page and save as PDF")
    else:
        print("❌ Presentation file not found")

def show_sharing_options():
    """Display sharing options"""
    print("\n" + "="*60)
    print("🚀 GULF GLOBAL CO - WEBSITE SHARING OPTIONS")
    print("="*60)
    print("\n1. 🌐 LOCAL SHARING (Current Network)")
    print("   • Start local server")
    print("   • Share URL with friends on same WiFi")
    print("   • URL: http://[YOUR_IP]:8000")

    print("\n2. 📄 PDF PRESENTATION")
    print("   • Create professional PDF presentation")
    print("   • Include screenshots and features")
    print("   • Perfect for client approval")

    print("\n3. 🌍 ONLINE HOSTING (Recommended)")
    print("   • Netlify: Drag & drop to netlify.com")
    print("   • GitHub Pages: Upload to GitHub")
    print("   • Vercel: Connect GitHub repository")

    print("\n4. 📱 MOBILE TESTING")
    print("   • Test on different devices")
    print("   • Check responsive design")
    print("   • Verify WhatsApp integration")

    print("\n" + "="*60)

def main():
    """Main function"""
    show_sharing_options()

    while True:
        print("\n🎯 Choose an option:")
        print("1. 🌐 Start Local Server")
        print("2. 📄 Open Presentation")
        print("3. 📱 Show Network Info")
        print("4. 🚀 Deploy to Netlify")
        print("5. ❌ Exit")

        choice = input("\nEnter your choice (1-5): ").strip()

        if choice == "1":
            try:
                start_local_server()
            except KeyboardInterrupt:
                print("\n⏹️  Server stopped")
                break
        elif choice == "2":
            open_presentation()
        elif choice == "3":
            ip = get_local_ip()
            print(f"\n🌍 Your Network Information:")
            print(f"📍 Local IP: {ip}")
            print(f"🔗 Share this URL: http://{ip}:8000")
            print(f"📱 Friends can access on same WiFi network")
        elif choice == "4":
            print("\n🚀 NETLIFY DEPLOYMENT STEPS:")
            print("1. Go to https://netlify.com")
            print("2. Drag and drop the 'httpdocs' folder")
            print("3. Get instant live URL")
            print("4. Share the URL with friends")
            print("5. No technical knowledge required!")
        elif choice == "5":
            print("👋 Goodbye!")
            break
        else:
            print("❌ Invalid choice. Please try again.")

if __name__ == "__main__":
    main()
