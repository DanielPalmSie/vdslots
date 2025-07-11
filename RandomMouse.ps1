# Добавляем объявление WinAPI-функций
Add-Type @"
using System;
using System.Runtime.InteropServices;
public class NativeMouse {
    [DllImport("user32.dll")]
    public static extern bool SetCursorPos(int X, int Y);
    [DllImport("user32.dll")]
    public static extern bool GetCursorPos(out POINT lpPoint);
    public struct POINT { public int X; public int Y; }
}
"@

# Бесконечный цикл с выходом по Ctrl-C
while ($true) {
    # Текущие координаты
    $pt = New-Object NativeMouse+POINT
    [NativeMouse]::GetCursorPos([ref]$pt) | Out-Null

    # Случайный сдвиг от –100 до +100 пикселей
    $dx = Get-Random -Minimum -100 -Maximum 101
    $dy = Get-Random -Minimum -100 -Maximum 101

    # Новые координаты
    $newX = [Math]::Max(0, $pt.X + $dx)
    $newY = [Math]::Max(0, $pt.Y + $dy)

    # Перемещаем курсор
    [NativeMouse]::SetCursorPos($newX, $newY) | Out-Null

    # Пауза 5 секунд
    Start-Sleep -Seconds 5
}
