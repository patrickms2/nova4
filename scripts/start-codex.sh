#!/bin/bash

clear

echo "================================="
echo "NOVA IMPLEMENTATION SESSION"
echo "================================="

echo
echo "Mission:"
grep -A80 "### CURRENT MISSION" NEXT.md

echo
echo "Launching Codex..."
echo

codex
