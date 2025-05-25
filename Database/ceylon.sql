-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 15, 2025 at 08:36 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ceylon`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `qty` int(11) DEFAULT NULL,
  `total_cost` double DEFAULT NULL,
  `product_product_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `userid`, `qty`, `total_cost`, `product_product_id`) VALUES
(177, 9, 2, 7.9, 6);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `nic` varchar(12) DEFAULT '',
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `dob` date DEFAULT NULL,
  `phone_personal` varchar(15) DEFAULT NULL,
  `phone_work` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `username`, `nic`, `email`, `password`, `dob`, `phone_personal`, `phone_work`, `address`, `postal_code`, `country`, `created_at`, `updated_at`) VALUES
(1, 'Venura Jayasingha', '123456789V', 'venurajayasingha1@gmail.com', '$2y$10$WVZejzLZAvCQeoosO4HCberXv0PPjJ4FZIwkreioECnOeTUEa73m6', '2003-06-01', '0763472796', '0382292467', 'No48/A,Samarahena Road,Aluthgama,Bandaragama', '12530', 'Sri Lanka', '2025-04-20 02:56:07', '2025-05-01 06:32:16'),
(2, 'gayashajayasingha', '129456789V', 'gayashajayasingha1@gmail.com', '$2y$10$13fdpjThIC9n..uBZ8pffeIzXDP1blQHIvzip50kfJ9q05e1kyil2', '2025-04-01', '0763462790', '0382292467', 'No48/A,Samarahena Road,Aluthgama,Bandaragama', '12530', 'Sri Lanka', '2025-04-20 06:09:06', '2025-04-20 06:09:06'),
(5, 'Gayasha Jayasingha', '', 'venurajayasingha10@gmail.com', '$2y$10$c7vBRb/a9B/iVU9ODPhPP.tktSkk.q1Njc7Sst5XFhb2jVgDP57OK', '0000-00-00', '0382292489', '0382292467', 'No48/A,Samarahena Road,Aluthgama,Bandaragama', '12530', 'Sri Lanka', '2025-04-20 09:15:09', '2025-04-20 15:15:58'),
(8, 'nadun', '', 'nadun@gmail.com', '$2y$10$aSpvZlnWedoahzSLqFk0aeakPFwXr3fZurUsW97C4iOPomluiDxri', NULL, '1234567890', '', 'test12', '1110043', 'Sri Lanka', '2025-05-13 02:46:13', '2025-05-13 06:27:55'),
(9, 'Pehasarani Gamage', '200310600592', 'pehasaranigamage@gmail.com', '$2y$10$WyETbGw7SZuuSP9fa4js5O9/zQ3asPPHV60Jn4ZOwE/3QYRfY8uba', '2022-07-12', '0715597985', '0768202029', '88/8 , Isuru Mawatha, Deniyaya, Matara', '81000', 'Sri Lanka', '2025-05-14 03:25:15', '2025-05-14 21:38:25'),
(10, 'Kamal Jayakantha', '200314457889', 'kamal@gmail.com', '$2y$10$ebIJv4v/SCTWc2SJcVJdL.96gNHolQHcA7TfIquKHyTBiyW3fRi5y', '2009-02-18', '0715597985', '0768202029', 'No.888/6,Temple Road,Ella', '81000', 'Sri Lanka', '2025-05-14 17:31:16', '2025-05-14 17:31:16');

-- --------------------------------------------------------

--
-- Table structure for table `cus_payment`
--

CREATE TABLE `cus_payment` (
  `payment_id` int(11) NOT NULL,
  `status` int(11) DEFAULT NULL,
  `amount` double DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `invoice_invoice_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice`
--

CREATE TABLE `invoice` (
  `invoice_id` int(11) NOT NULL,
  `amount` double DEFAULT NULL,
  `invoice_date` datetime DEFAULT NULL,
  `customer_cus_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `payment_intent_id` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `payment_intent_id`, `amount`, `status`, `created_at`) VALUES
(237, 9, 'ORD-682588785F160', 15.95, 'Pending', '2025-05-15 06:23:52');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `created_at`) VALUES
(123, 237, 411937, 1, 12.00, '2025-05-15 06:23:52'),
(124, 237, 6, 1, 3.95, '2025-05-15 06:23:52');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `product_id` int(11) NOT NULL,
  `top_email` varchar(100) DEFAULT NULL,
  `name` varchar(45) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  `price` double DEFAULT NULL,
  `no_of_products` int(11) DEFAULT NULL,
  `product_images` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`product_id`, `top_email`, `name`, `description`, `weight`, `price`, `no_of_products`, `product_images`) VALUES
(3, 'venurajayasingha1@gmail.com', 'Turmeric Powder', 'Organic ground turmeric with vibrant color and strong aroma', 100, 4.99, 0, 'https://info.ehl.edu/hubfs/EHL-Passugg_Blog_Kurkuma_Titelbild_001.jpg'),
(4, 'venurajayasingha1@gmail.com', 'Cumin Seeds', 'Whole cumin seeds with earthy flavor, perfect for Indian cuisine', 200, 3.49, 0, 'https://cdn.britannica.com/59/219359-050-662D86EA/Cumin-Spice.jpg'),
(5, 'venurajayasingha1@gmail.com', 'Black Pepper', 'Whole black peppercorns, freshly packed for maximum flavor', 100, 5.25, 27, 'https://m.media-amazon.com/images/I/9146kIs-ZzL._SL1500_.jpg'),
(6, 'venurajayasingha1@gmail.com', 'Cinnamon Sticks', 'Premium cinnamon sticks from Sri Lanka, ideal for desserts and drinks', 150, 3.95, 58, 'https://d39ltat5ucvvhk.cloudfront.net/uploads/images/202112/img_1920x_61b887230ce0c3-07664878-84341299.jpg'),
(7, 'contact@freshproduce.com', 'Paprika', 'Smoked paprika powder with a deep red hue and smoky flavor', 100, 4.5, 86, 'https://finch.lk/wp-content/uploads/2021/04/120385-Paprika-Powder-FRONT.jpg'),
(8, 'contact@freshproduce.com', 'Catch Chili Powder', 'Spicy red chili powder made from sun-dried chilies', 100, 3.99, 92, 'https://kandyspice.shop/wp-content/uploads/2023/09/Chili-Powder.png'),
(9, NULL, 'Bay Leaves', 'Dried bay leaves for soups, stews, and curries', 100, 2.75, 147, 'https://www.foodandwine.com/thmb/xUmB0l8OO7H8IeENsLDiYIyH6Ec=/1500x0/filters:no_upscale():max_bytes(150000):strip_icc()/Bay-Leaves-Explained-What-to-Do-With-Them-Fresh-or-Dried-FT-BLOG0424-02-bf7d6af2336143dba910f38a80ab408d.jpg'),
(10, NULL, 'Garam Masala', 'Traditional Indian spice blend made fresh from whole spices', 100, 6.49, 64, 'https://assets.hotcooking.co.uk/portrait45/dishoom_garam_masala_large.jpg'),
(11, NULL, 'Coriander Powder', 'Finely ground coriander with citrusy undertones', 100, 3.49, 106, 'https://www.jkcart.com/uploads/blogs/blogImg_189264145917399726424736825375.png'),
(12, NULL, 'Fennel Seeds', 'Sweet and aromatic fennel seeds for cooking and digestion', 0, 2.99, 132, 'https://fooddrinklife.com/wp-content/uploads/2024/01/47582496_fennel-seeds-with-fennel-plant.jpg'),
(13, NULL, 'Mustard Seeds', 'Black mustard seeds, perfect for pickles and curries', 0, 2.5, 127, 'https://fooddrinklife.com/wp-content/uploads/2024/02/mustard-seeds-4.jpg'),
(14, NULL, 'Cloves', 'Strong, aromatic whole cloves used in baking and spice blends', 0, 4.99, 95, 'https://www.wwc.world/image/cache/catalog/Spice%20Set%20/cloves-800x800.png'),
(15, NULL, 'Nutmeg', 'Whole nutmeg nuts, ideal for grating fresh into recipes', 0, 6.25, 58, 'https://assets.bonappetit.com/photos/636d0aad2c36e1afe66bed0c/16:9/w_2560%2Cc_limit/nutmeg.jpg'),
(16, NULL, 'Cardamom Pods', 'Green cardamom pods, handpicked and highly fragrant', 0, 7.99, 53, 'https://greenfield.organic/wp-content/uploads/2019/10/Cardamom-Whole.jpg'),
(17, NULL, 'Curry Leaves', 'Sun-dried curry leaves used in South Indian dishes', 0, 2.99, 95, 'https://media.post.rvohealth.io/wp-content/uploads/2020/03/curry-leaves-732x549-thumbnail-732x549.jpg'),
(18, NULL, 'Red Lentils', 'Split red lentils (masoor dal), quick-cooking and nutritious', 1, 3.89, 199, 'https://www.keepingthepeas.com/wp-content/uploads/2022/11/red-lentils-in-wood-bowl.jpg'),
(19, NULL, 'Basmati Rice', 'Long-grain basmati rice aged for fragrance and fluffiness', 2, 9.99, 150, 'https://www.proportionalplate.com/wp-content/uploads/2017/01/Persian-RiceAug-23-202243.jpg'),
(20, NULL, 'Chickpeas', 'Dried chickpeas (kabuli chana), rich in protein and fiber', 1, 2.99, 178, 'https://www.allrecipes.com/thmb/WdQzwYsrWX0-6zRprlfn7OitWN8=/1500x0/filters:no_upscale():max_bytes(150000):strip_icc()/81548-roasted-chickpeas-ddmfs-0442-1x2-hero-295c03efec90435a8588848f7e50f0bf.jpg'),
(21, NULL, 'Saffron', 'Pure saffron threads from Kashmir, intensely aromatic', 0, 14.99, 38, 'https://media.post.rvohealth.io/wp-content/uploads/2020/11/saffron-732x549-thumbnail.jpg'),
(22, NULL, 'Asafoetida (Hing)', 'Strong-smelling spice used in Indian vegetarian cooking', 0, 3.75, 90, 'https://images-cdn.ubuy.com.sa/6677b5fbda06c73d170358af-asafoedita-hing-50g.jpg'),
(411937, 'freshgoods@gmail.com', 'Pure Green Tea', 'Dilmah - Pure Green Tea (20 tea bags). 100% pure single origin Ceylon green tea sourced from the Nuwara Eliya region.', 105, 12, 22, 'https://shop.dilmahtea.co.za/cdn/shop/articles/7.jpg?v=1679564042');

-- --------------------------------------------------------

--
-- Table structure for table `product_has_customer`
--

CREATE TABLE `product_has_customer` (
  `product_product_id` int(11) NOT NULL,
  `customer_cus_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saved_card`
--

CREATE TABLE `saved_card` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `card_holder_name` varchar(100) NOT NULL,
  `card_number` varchar(255) NOT NULL,
  `card_type` varchar(20) DEFAULT NULL,
  `expiry_date` varchar(7) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saved_card`
--

INSERT INTO `saved_card` (`id`, `user_id`, `card_holder_name`, `card_number`, `card_type`, `expiry_date`, `created_at`, `updated_at`) VALUES
(43, 9, 'Pehasarani', '/SpX4CiUJMARsusZ/7lCI2Q49RqGysJui5R4l2YOxuM=', 'Visa', '12/26', '2025-05-15 02:21:18', NULL),
(44, 10, 'Kamal', 'wWC9bIXgxLwLH9eLSKfrIi4HvE/yn965NBfaVARbhTU=', NULL, '06/30', '2025-05-15 02:34:20', NULL),
(45, 10, 'Kamal', 'wWC9bIXgxLwLH9eLSKfrIi4HvE/yn965NBfaVARbhTU=', NULL, '06/30', '2025-05-15 02:34:25', NULL),
(46, 10, 'Kamal', 'wWC9bIXgxLwLH9eLSKfrIi4HvE/yn965NBfaVARbhTU=', 'Visa', '06/30', '2025-05-15 02:34:38', NULL),
(47, 9, 'Peha', '/SpX4CiUJMARsusZ/7lCI2Q49RqGysJui5R4l2YOxuM=', 'Visa', '12/26', '2025-05-15 11:53:52', '2025-05-15 06:34:24');

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `supplier_id` int(11) NOT NULL,
  `name` varchar(45) DEFAULT NULL,
  `nic` varchar(45) DEFAULT NULL,
  `email` varchar(45) DEFAULT NULL,
  `business_email` varchar(45) DEFAULT NULL,
  `business_name` varchar(45) DEFAULT NULL,
  `business_registration_code` varchar(45) DEFAULT NULL,
  `card_no` varchar(45) DEFAULT NULL,
  `card_name` varchar(45) DEFAULT NULL,
  `security_code` varchar(45) DEFAULT NULL,
  `exp_date` varchar(45) DEFAULT NULL,
  `phone_number` int(11) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`supplier_id`, `name`, `nic`, `email`, `business_email`, `business_name`, `business_registration_code`, `card_no`, `card_name`, `security_code`, `exp_date`, `phone_number`, `password`) VALUES
(1, 'Uvindu Pramuditha', '200205865258', 'arrr9281@gmail.com', 'arrr9281@gmail.com', 'jk', '1123456', NULL, NULL, NULL, NULL, 750273901, '123456'),
(2, 'Uvindu Pramuditha', '200205865258', 'arrr9281@gmail.com', 'arrr9281@gmail.com', 'jk', '1123456', NULL, NULL, NULL, NULL, 750273901, '123456'),
(3, 'Uvindu Pramuditha', '200205865258', 'arrr9281@gmail.com', 'arrr9281@gmail.com', 'jk', '1123456', NULL, NULL, NULL, NULL, 750273901, '123456'),
(4, 'Uvindu Pramuditha', '200205865258', 'arrr9281@gmail.com', 'arrr9281@gmail.com', 'jk', '1123456', NULL, NULL, NULL, NULL, 750273901, '123456'),
(5, 'Uvindu Pramuditha', '200205865258', 'arrr9281@gmail.com', 'arrr9281@gmail.com', 'jk', '1123456', NULL, NULL, NULL, NULL, 750273901, '123456'),
(6, 'Uvindu Pramuditha', '200205865258', 'arrr9281@gmail.com', 'arrr9281@gmail.com', 'jk', '1123456', NULL, NULL, NULL, NULL, 750273901, '123456');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `business_name` varchar(100) NOT NULL,
  `business_email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `business_address` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `business_name`, `business_email`, `password`, `business_address`, `contact_number`, `created_at`, `updated_at`) VALUES
(1, 'Jayamal Stores', 'jayamalstores@gmail.com', '$2y$10$A9Gf5WzZ0AkAuvvYdPb/5uXBwAMLq7zwINPHdlZ2sZSY5tf0G3kwC', 'No. 45, Temple Road\r\nColombo', '0715359228', '2025-05-15 06:05:07', '2025-05-15 06:05:07'),
(2, 'Fresh Goods', 'freshgoods@gmail.com', '$2y$10$i7c1wLPv.3vbw11i4c8vQeflcSHRgzZpuRRe3uN7Q0ZA9drAEJgr.', '478, Meekanuwa Road,\r\nKandy.', '0715359172', '2025-05-15 06:07:44', '2025-05-15 06:07:44');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_payment`
--

CREATE TABLE `supplier_payment` (
  `payment_id` int(11) NOT NULL,
  `amount` double DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `month` varchar(45) DEFAULT NULL,
  `nic` varchar(45) DEFAULT NULL,
  `supplier_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `supplier_payment`
--

INSERT INTO `supplier_payment` (`payment_id`, `amount`, `status`, `payment_date`, `month`, `nic`, `supplier_name`) VALUES
(1, 10000, 1, '2025-05-15 08:12:59', '2025-06', '200310600597', 'Fresh Goods');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `fk_cart_product1_idx` (`product_product_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `cus_payment`
--
ALTER TABLE `cus_payment`
  ADD PRIMARY KEY (`payment_id`);

--
-- Indexes for table `invoice`
--
ALTER TABLE `invoice`
  ADD PRIMARY KEY (`invoice_id`),
  ADD KEY `fk_invoice_customer1_idx` (`customer_cus_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `product_has_customer`
--
ALTER TABLE `product_has_customer`
  ADD PRIMARY KEY (`product_product_id`,`customer_cus_id`),
  ADD KEY `fk_product_has_customer_customer1_idx` (`customer_cus_id`),
  ADD KEY `fk_product_has_customer_product1_idx` (`product_product_id`);

--
-- Indexes for table `saved_card`
--
ALTER TABLE `saved_card`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`),
  ADD UNIQUE KEY `business_email` (`business_email`);

--
-- Indexes for table `supplier_payment`
--
ALTER TABLE `supplier_payment`
  ADD PRIMARY KEY (`payment_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=178;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `cus_payment`
--
ALTER TABLE `cus_payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;

--
-- AUTO_INCREMENT for table `invoice`
--
ALTER TABLE `invoice`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=238;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=685702;

--
-- AUTO_INCREMENT for table `saved_card`
--
ALTER TABLE `saved_card`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `supplier_payment`
--
ALTER TABLE `supplier_payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `saved_card`
--
ALTER TABLE `saved_card`
  ADD CONSTRAINT `saved_card_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `customers` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
