-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Янв 12 2026 г., 04:32
-- Версия сервера: 10.6.17-MariaDB-log
-- Версия PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `eigiva_tunedup`
--

-- --------------------------------------------------------

--
-- Структура таблицы `cars`
--

CREATE TABLE `cars` (
  `id` int(11) NOT NULL,
  `brand` varchar(50) NOT NULL,
  `model` varchar(50) NOT NULL,
  `series` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `cars`
--

INSERT INTO `cars` (`id`, `brand`, `model`, `series`, `description`) VALUES
(1, 'Chevrolet', 'Corvette', 'C7/Stingray', NULL),
(2, 'AUDI', 'A4', '', NULL),
(3, 'AUDI', 'Q5', '', NULL),
(4, 'AUDI', 'A6', '', NULL),
(5, 'BMW', '3 Series', 'F30', NULL),
(6, 'BMW', '3 Series', 'G20', NULL),
(7, 'BMW', 'X5', '', NULL),
(8, 'BMW', '5 Series', 'F10', NULL),
(9, 'BMW', '5 Series', 'G30', NULL),
(10, 'Mercedes-Benz', 'C-Class', '', NULL),
(11, 'Mercedes-Benz', 'E-Class', '', NULL),
(12, 'Mercedes-Benz', 'GLC', '', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `item_type` enum('tool','sticker','color','light') NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `car_colors`
--

CREATE TABLE `car_colors` (
  `id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `hex_code` char(7) DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `car_colors`
--

INSERT INTO `car_colors` (`id`, `car_id`, `name`, `hex_code`, `image`, `price`) VALUES
(2, 1, 'Coral Red', '#c8565b', 'car_coral_red.png', 159.99),
(4, 1, 'Orchid Pink', '#d663a6', 'car_orchid_pink.png', 29.99),
(5, 1, 'Lavender Purple', '#bb62ca', 'car_lavender_purple.png', 29.99),
(6, 1, 'Purple Mist', '#927ab3', 'car_purple_mist.png', 29.99),
(7, 1, 'Sky Blue', '#6F85C7', 'car_sky_blue.png', 29.99),
(8, 1, 'Denim Blue', '#758DB9', 'car_denim_blue.png', 29.99),
(9, 1, 'Mint Green', '#7CA6A8', 'car_mint_green.png', 29.99),
(10, 1, 'Fresh Green', '#7FB18D', 'car_fresh_green.png', 29.99),
(11, 1, 'Sunbeam Yellow', '#AFA94B', 'car_sunbeam_yellow.png', 29.99),
(12, 1, 'Desert Gold', '#B7905A', 'car_desert_gold.png', 34.99),
(13, 1, 'Silver', '#B7B7B7', 'car_silver.png', 39.99),
(14, 1, 'Black', '#000000', 'car_black.png', 29.99),
(15, 1, 'White', '#dcdcdc', 'car_white.png', 19.99);

-- --------------------------------------------------------

--
-- Структура таблицы `car_lights`
--

CREATE TABLE `car_lights` (
  `id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `hex_code` char(7) DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `car_lights`
--

INSERT INTO `car_lights` (`id`, `car_id`, `name`, `hex_code`, `image`, `price`) VALUES
(1, 1, 'Rich red', '#FF4B4B', 'light_red.png', 49.99),
(2, 1, 'Pink', '#FF5BBE', 'light_pink.png', 49.99),
(3, 1, 'Purple', '#D552FF', 'light_purple.png', 49.99),
(4, 1, 'Skywave Blue', '#6594F5', 'light_skywave_blue.png', 49.99),
(5, 1, 'Light Blue', '#63CAEB', 'light_light_blue.png', 49.99),
(6, 1, 'Fresh Green', '#68EFB9', 'light_fresh_green.png', 49.99),
(7, 1, 'Natural Green', '#65BD47', 'light_natural_green.png', 49.99),
(8, 1, 'Yellow', '#F5E850', 'light_yellow.png', 49.99),
(9, 1, 'Orange', '#FFA26F', 'light_orange.png', 49.99);

-- --------------------------------------------------------

--
-- Структура таблицы `car_tools`
--

CREATE TABLE `car_tools` (
  `id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `car_tools`
--

INSERT INTO `car_tools` (`id`, `car_id`, `tool_id`) VALUES
(1, 1, 2),
(2, 2, 18),
(3, 7, 15),
(4, 8, 11),
(5, 10, 12),
(6, 12, 9);

-- --------------------------------------------------------

--
-- Структура таблицы `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_type` enum('tool','sticker','color','light') NOT NULL,
  `item_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `shipment_address` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `payment_method` enum('cash','card') NOT NULL,
  `status` enum('confirmed','in_delivery','cancelled','delivered') DEFAULT 'confirmed',
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_type` enum('tool','sticker','color','light') NOT NULL,
  `item_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `item_type` enum('tool','sticker','color','light') NOT NULL,
  `item_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `stickers`
--

CREATE TABLE `stickers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `stickers`
--

INSERT INTO `stickers` (`id`, `name`, `description`, `price`, `image`) VALUES
(1, 'Smile Cat', 'Very smiled cat', 5.99, 'Cat_Sticker.jpg\r\n'),
(2, 'Bo-bo tomato', 'Tomato in pain', 5.99, 'Tomato_Sticker.jpg'),
(3, 'Happy tomato', 'Fine Tomato', 5.99, 'Happy_Tomato.JPG'),
(4, 'Orange', 'Fine Orange', 5.99, 'Orange.JPG'),
(5, 'SmileFace', 'Smiling face', 5.99, 'Smile_Face.JPG'),
(6, 'Poor Dog', 'Very poor dog', 5.99, 'Arlekino_Dog.JPG'),
(7, 'Bad Virus', 'With star', 5.99, 'Virus.JPG'),
(8, 'Star cat', 'Cat with star', 5.99, 'Star_Cat.JPG'),
(9, 'Cat with fish', 'Cat with fish - happy cat', 5.99, 'Cat_with_Fish.JPG'),
(10, 'Cat with tongue', 'Playful cat', 5.99, 'Tongue_Cat.JPG'),
(11, 'Finish flags', 'Finish', 5.99, 'Finish.JPG'),
(12, 'Batman', 'Where is Detonator?', 5.99, 'Batman.JPG'),
(13, 'Porshe', 'Porshe', 5.99, 'Porsche.JPG'),
(14, 'Racing Porsche', 'Racing Porsche', 5.99, 'Racing_Porsche.JPG'),
(15, '8-bit Cat', '8-bit Cat from mysterious game', 5.99, '8-bit_Cat.JPG'),
(16, 'GentleFrog', 'GentleFrog', 5.99, 'GentleFrog.JPG'),
(17, 'Bibizyana', 'Bibizyana', 5.99, 'Bibizyana.JPG');

-- --------------------------------------------------------

--
-- Структура таблицы `tools`
--

CREATE TABLE `tools` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `tools`
--

INSERT INTO `tools` (`id`, `name`, `description`, `price`, `image`) VALUES
(2, 'IKEA Tools', 'Tools from ikea', 25.00, 'IKEA Tools.jpg'),
(3, 'Xiaomi Tools', 'Tools from China', 35.00, 'Tools1.jpg'),
(4, 'Red LED Taillight AUDI', 'Taillight for AUDI', 79.99, 'Detail1.png'),
(5, 'Door Sill BMW', 'Door Sill for bmw', 79.99, 'Detail2.png'),
(7, 'Front Bumper BMW', 'Front Bumper for BMW', 79.99, 'Detail4.png'),
(8, 'Rear Bumper BMW', 'Rear Bumper for BMW', 79.99, 'Detail5.png'),
(9, 'Rear Bumper Mercedes-Benz', 'Rear Bumper for Mercedes-Benz', 79.99, 'Detail6.png'),
(10, 'Blue LED Headlight BMW', 'Headlight for BMW', 79.99, 'Detail7.png'),
(11, 'Yellow LED Headlight BMW', 'Yellow LED Headlight BMW', 79.99, 'Detail8.png'),
(12, 'Yellow Taillight Mercedes-Benz', 'Yellow LED Taillight for Mercedes-Benz', 79.99, 'Detail9.png'),
(13, 'Red Taillight Mercedes-Benz', 'Red Taillight for Mercedes-Benz', 79.99, 'Detail10.png'),
(14, 'Gold Headlight BMW', 'Gold Headlight for BMW', 79.99, 'Detail11.png'),
(15, 'White Headlight BMW', 'White Headlight for BMW', 79.99, 'Detail12.png'),
(16, 'Yellow Headlight AUDI', 'Yellow Headlight for AUDI', 79.99, 'Detail13.png'),
(17, 'Yellow Taillight AUDI', 'Yellow Taillight for AUDI', 79.99, 'Detail14.png'),
(18, 'Red Taillight AUDI', 'Red Taillight for AUDI', 79.99, 'Detail15.png');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `car_colors`
--
ALTER TABLE `car_colors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `car_id` (`car_id`);

--
-- Индексы таблицы `car_lights`
--
ALTER TABLE `car_lights`
  ADD PRIMARY KEY (`id`),
  ADD KEY `car_id` (`car_id`);

--
-- Индексы таблицы `car_tools`
--
ALTER TABLE `car_tools`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `car_id` (`car_id`,`tool_id`),
  ADD KEY `tool_id` (`tool_id`);

--
-- Индексы таблицы `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`item_type`,`item_id`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Индексы таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `stickers`
--
ALTER TABLE `stickers`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `tools`
--
ALTER TABLE `tools`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT для таблицы `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT для таблицы `car_colors`
--
ALTER TABLE `car_colors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT для таблицы `car_lights`
--
ALTER TABLE `car_lights`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `car_tools`
--
ALTER TABLE `car_tools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT для таблицы `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT для таблицы `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT для таблицы `stickers`
--
ALTER TABLE `stickers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT для таблицы `tools`
--
ALTER TABLE `tools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `car_colors`
--
ALTER TABLE `car_colors`
  ADD CONSTRAINT `car_colors_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `car_lights`
--
ALTER TABLE `car_lights`
  ADD CONSTRAINT `car_lights_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `car_tools`
--
ALTER TABLE `car_tools`
  ADD CONSTRAINT `car_tools_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `car_tools_ibfk_2` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
