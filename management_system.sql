-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2026-11-21 05:55:10
-- 服务器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `management_system`
--

-- --------------------------------------------------------

--
-- 表的结构 `book`
--

CREATE TABLE `book` (
  `book_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `available_quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `borrowing`
--

CREATE TABLE `borrowing` (
  `borrowing_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `borrow_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date NOT NULL,
  `status` varchar(20) NOT NULL,
  `fine_amount` decimal(8,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `category`
--

CREATE TABLE `category` (
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `conversation`
--

CREATE TABLE `conversation` (
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `conversation`
--

INSERT INTO `conversation` (`conversation_id`, `user_id`, `created_at`) VALUES
(1, 17, '2026-11-21 00:48:41'),
(2, 17, '2026-11-21 00:50:26'),
(3, 17, '2026-11-21 00:56:39'),
(4, 17, '2026-11-21 01:12:32'),
(5, 17, '2026-11-21 03:02:24'),
(6, 17, '2026-11-21 03:21:01'),
(7, 17, '2026-11-21 03:40:10'),
(8, 17, '2026-11-21 03:40:42'),
(9, 17, '2026-11-21 03:41:22'),
(10, 17, '2026-11-21 03:49:11');

-- --------------------------------------------------------

--
-- 表的结构 `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text NOT NULL,
  `feedback_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `fine`
--

CREATE TABLE `fine` (
  `fine_id` int(11) NOT NULL,
  `borrowing_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `payment_status` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL,
  `paid_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `message`
--

CREATE TABLE `message` (
  `message_id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_type` enum('member','ai') NOT NULL,
  `message_content` text NOT NULL,
  `sent_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `message`
--

INSERT INTO `message` (`message_id`, `conversation_id`, `sender_type`, `message_content`, `sent_at`) VALUES
(1, 2, 'member', 'hi', '2026-11-21 00:50:27'),
(2, 2, 'ai', 'Hello! 👋 Welcome to the Story Vault. How can I assist you today? Whether you’re looking for a book recommendation, need help with a reservation, want to know our borrowing policies, or have any other library‑related question, I’m here to help!', '2026-11-21 00:50:27'),
(3, 2, 'member', 'how can i borrow', '2026-11-21 00:50:38'),
(4, 2, 'ai', 'Sure thing! Here’s a quick guide to borrowing books (or any other items) from Story Vault:\n\n---\n\n### 1️⃣ Get a Library Card\n| What you need | How to get it |\n|---------------|---------------|\n| **Proof of identity** (driver’s license, state ID, or passport) | Bring it to the front desk or upload a clear photo through the **Story Vault Member Portal**. |\n| **Proof of address** (utility bill, lease, or a recent bank statement) | Same as above – a digital copy works. |\n| **Age requirement** | Anyone 12 + can get a standard card. If you’re under 12, a parent/guardian can get a **Junior Card** linked to their account. |\n| **Fee** | **Free** for residents of our service area. Non‑residents can apply for a **Visitor Card** for a $5 annual fee. |\n\n*Once your application is approved (usually within a few minutes online, or the same day in‑person), you’ll receive a barcode card that you can print, save on your phone, or pick up at the desk.*\n\n---\n\n### 2️⃣ Find Something You Want\n- **In‑person:** Browse the shelves or use the self‑service kiosks to search the catalog.\n- **Online:** Log into the **Story Vault Member Portal** or use the **Story Vault App** → “Search Catalog.”  \n  *You can filter by format (print, ebook, audiobook, DVD, etc.), language, genre, and availability.*\n\n---\n\n### 3️⃣ Check Out the Item\n| Method | Steps |\n|--------|-------|\n| **At the circulation desk** | Hand the item (or its barcode) to the librarian. Show your library card. They’ll scan it and give you a receipt with the due date. |\n| **Self‑checkout kiosk** | Scan your card, then scan the item’s barcode. The machine prints a receipt (or you can get an e‑receipt via email/text). |\n| **Online (e‑books/audiobooks)** | Click “Borrow” in the portal or app. The title will appear instantly in your **Loans** shelf. You can start', '2026-11-21 00:50:38'),
(5, 3, 'member', 'hi', '2026-11-21 00:56:39'),
(6, 3, 'ai', 'Hello! 👋 Welcome to Story Vault. How can I assist you today? Whether you’re looking for a book recommendation, need help with a reservation, or have questions about borrowing policies, I’m here to help!', '2026-11-21 00:56:40'),
(7, 3, 'member', 'how can i borrow', '2026-11-21 00:56:45'),
(8, 3, 'ai', '### How to Borrow Books at Story Vault 📚\n\nBelow is a quick‑step guide for **both physical books** (the ones on our shelves) and **digital titles** (e‑books & audiobooks).\n\n---\n\n## 1️⃣ Make Sure You’re a Member\n| What you need | How to get it |\n|---------------|---------------|\n| **Valid library card** (or a temporary “guest pass” if you’re visiting) | • **In‑person:** Bring a photo ID and proof of address to the front desk. <br>• **Online:** Sign up on our website → “Create Account” → Fill in name, email, address, and upload a scan of your ID. You’ll receive a digital card within a few minutes. |\n| **Active account** (no unpaid fines > $10) | Check your account status on the “My Account” page or ask a staff member. |\n\n---\n\n## 2️⃣ Find a Title You Want\n\n### A. Physical books\n1. **Search** the catalog (website, app, or the public computer terminals).  \n2. **Check availability** – the record will show:  \n   *“Available – Shelf X”* or *“Checked out – due MM/DD, 2 holds placed.”*  \n3. **Note the call number** (e.g., **823.914 FIC Smi**) – you’ll need it to locate the book on the shelves.\n\n### B. Digital titles (e‑books & audiobooks)\n1. Use the **e‑collection** tab in the catalog.  \n2. Click **“Borrow”** – the title will be added instantly to your “Loans” list in the app (OverDrive, Libby, or our native reader).\n\n---\n\n## 3️⃣ Borrowing the Book\n\n### Physical books – **In‑person**\n| Step | What to do |\n|------|------------|\n| **a.** Go to the **Circulation Desk** (or the self‑checkout kiosk if you have a card with a barcode). |\n| **b.** Scan your library card **and** the book’s barcode. |\n| **c.', '2026-11-21 00:56:45'),
(9, 3, 'member', 'how to add book in wishlist', '2026-11-21 00:57:16'),
(10, 3, 'ai', '### Adding a Book to Your **Wish‑List** (a.k.a. “Save for Later”)  \n\nStory Vault lets you keep a personal wish‑list of titles you’d like to read later, whether they’re currently checked out, on hold, or just on your radar. Below are the step‑by‑step instructions for the **Web Catalog**, the **Mobile App**, and the **Self‑Service Kiosk**.\n\n---\n\n## 1️⃣ From the **Story Vault Website** (desktop or mobile browser)\n\n| Step | Action |\n|------|--------|\n| **1. Sign in** | Click **“Log In”** at the top‑right and enter your library card number + PIN (or your email/password if you use a digital card). |\n| **2. Search** | Use the search bar to find the title you want. You can search by title, author, ISBN, or keyword. |\n| **3. Open the record** | Click the book’s title to view its full record page. |\n| **4. Locate the wish‑list button** | On the right‑hand side (or under the “Quick actions” bar on mobile) you’ll see a heart‑shaped icon labeled **“Add to Wish‑List”** or **“Save for Later.”** |\n| **5. Click it** | The icon turns solid (or the text changes to “In Wish‑List”). A small toast message will confirm: *“Title added to your Wish‑List.”* |\n| **6. View/Manage** | Go to **My Account → Wish‑List** to see all saved titles, remove items, or place a hold directly from there. |\n\n---\n\n## 2️⃣ From the **Story Vault Mobile App** (iOS / Android)\n\n1. **Open the app** and sign in (if you haven’t already).  \n2. Tap the **🔍 Search** icon and find the book.  \n3. On the book’s detail screen, tap the **❤️ Heart** button labeled **“Save”** or **“Add to', '2026-11-21 00:57:16'),
(11, 4, 'member', 'hi', '2026-11-21 01:12:33'),
(12, 4, 'ai', 'Hello! 👋 How can I help you today? Whether you’re looking for a book recommendation, need help with a reservation, or have any questions about our library services, just let me know!', '2026-11-21 01:12:33'),
(13, 5, 'member', 'hi', '2026-11-21 03:02:25'),
(14, 5, 'ai', 'Hello! 👋\n\nI’ve checked your library account, and currently there are no borrowing records, reservation records, fine records, or wishlist items linked to your profile.\n\nIf you have any questions or need help with something else—like finding a book, making a reservation, or understanding the library services—just let me know! I\'m happy to help.', '2026-11-21 03:02:25'),
(15, 5, 'member', 'what inside my wishlist', '2026-11-21 03:02:50'),
(16, 5, 'ai', 'Your wishlist is currently empty—there are no books or items saved in it at the moment. If you’d like to add something, just let me know the title (or author, genre, etc.) and I’ll help you add it to your wishlist! 😊', '2026-11-21 03:02:50'),
(17, 6, 'member', 'hi', '2026-11-21 03:21:02'),
(18, 6, 'ai', 'Hello! I\'m the **Story Vault Assistant**. I’m here to help you with any library‑related information, such as:\n\n- Book details  \n- Your borrowing records  \n- Your reservation records  \n- Any fines you may have  \n- Your wishlist  \n\nAt the moment, your account doesn’t have any borrowing, reservation, fine, or wishlist records. If you’d like to explore books, place a reservation, or need help with anything else, just let me know!', '2026-11-21 03:21:02'),
(19, 10, 'member', 'hi', '2026-11-21 03:49:13'),
(20, 10, 'ai', 'Hello! I\'m the **Story Vault Assistant**. I can help you with:\n\n- Book information  \n- Your borrowing records  \n- Your reservation records  \n- Your fine records  \n- Your wishlist records  \n\nAccording to the current database, you don’t have any borrowing, reservation, fine, or wishlist records at the moment. If you’d like to search for books, place a reservation, or anything else, just let me know!', '2026-11-21 03:49:13'),
(21, 10, 'member', 'hi、', '2026-11-21 03:53:52'),
(22, 10, 'ai', 'こんにちは！私は **Story Vault アシスタント** です。以下のことをお手伝いできます：\n\n- 書籍情報の検索  \n- あなたの貸出記録の確認  \n- 予約記録の確認・新規予約  \n- 未払いの延滞金の確認  \n- ウィッシュリストの管理  \n\n現在のデータベースを見ると、**貸出記録、予約記録、延滞金、ウィッシュリストはどれもありません**。何か探している本があったり、予約したい本があれば教えてくださいね！', '2026-11-21 03:53:52'),
(23, 10, 'member', 'hi', '2026-11-21 03:53:57'),
(24, 10, 'ai', 'Hello again! I’m the **Story Vault Assistant**. I’m here to help you with any library‑related information—book searches, borrowing history, reservations, fines, or your wishlist. Since your current records are all empty, just let me know what you’d like to do or ask about, and I’ll gladly assist!', '2026-11-21 03:53:57'),
(25, 10, 'member', '你好', '2026-11-21 03:54:03'),
(26, 10, 'ai', '你好！我是 **Story Vault 助手**，可以帮助你查询和管理以下内容：\n\n- 书籍信息  \n- 你的借阅记录  \n- 预约记录  \n- 罚款记录（包括未付款状态的说明）  \n- 心愿单记录  \n\n根据当前数据库，你目前 **没有任何借阅、预约、罚款或心愿单记录**。如果你想查询书籍、进行预约、查看或添加心愿单，或者有其他任何需求，请告诉我，我很乐意帮忙！', '2026-11-21 03:54:03');

-- --------------------------------------------------------

--
-- 表的结构 `reservation`
--

CREATE TABLE `reservation` (
  `reservation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `reservation_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `status` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `token`
--

CREATE TABLE `token` (
  `id` int(11) NOT NULL,
  `expire` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_type` varchar(50) NOT NULL,
  `verification_code` varchar(10) NOT NULL,
  `is_used` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `token`
--

INSERT INTO `token` (`id`, `expire`, `user_id`, `token_type`, `verification_code`, `is_used`, `created_at`) VALUES
(1, '2026-11-20 00:03:06', 6, 'email_verification', '590679', 0, '2026-11-19 23:53:06'),
(2, '2026-11-20 00:22:46', 12, 'email_verification', '724886', 0, '2026-11-20 00:12:46'),
(3, '2026-11-20 00:24:06', 13, 'email_verification', '141729', 1, '2026-11-20 00:14:06'),
(4, '2026-11-20 00:30:21', 14, 'email_verification', '226580', 1, '2026-11-20 00:20:21'),
(5, '2026-11-20 00:43:31', 15, 'email_verification', '285848', 0, '2026-11-20 00:33:31'),
(6, '2026-11-20 00:44:08', 16, 'email_verification', '517277', 0, '2026-11-20 00:34:08'),
(7, '2026-11-20 00:58:39', 17, 'email_verification', '976671', 1, '2026-11-20 00:48:39');

-- --------------------------------------------------------

--
-- 表的结构 `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(100) NOT NULL,
  `profile_photo` varchar(255) NOT NULL,
  `valid` tinyint(1) NOT NULL,
  `email_verified` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `user`
--

INSERT INTO `user` (`user_id`, `username`, `email`, `password`, `role`, `profile_photo`, `valid`, `email_verified`) VALUES
(1, 'Emily', 'emilychng8868@gmail.com', '53ac64aaf5c880b408d34450e1807a61a61fa243', '', '', 0, 0),
(2, 'Ivynghuyxing', 'ivynghuyxingivy@gmail.comivy', '2cffc7763b3b69433c819536f1b490c6fbba6292', '', '', 0, 0),
(3, 'Ivy', 'hojouyee@gmail.com', 'fa5ae93dd34a3ef44f2b8051fdb0eb130f1cfcfa', 'member', '6afc4577e0bc8.jpg', 0, 0),
(4, 'ivy', 'ivynghuyxing@gmail.com', 'fa5ae93dd34a3ef44f2b8051fdb0eb130f1cfcfa', 'member', '6aff0690ae433.jpg', 0, 0),
(5, 'Ivy', 'ivynghuy@gmail.com', 'fa5ae93dd34a3ef44f2b8051fdb0eb130f1cfcfa', 'admin', '', 0, 0),
(6, 'Ivy', 'ngivy808@gmail.com', 'fa5ae93dd34a3ef44f2b8051fdb0eb130f1cfcfa', 'member', '', 0, 0),
(12, 'Ivy', 'ngivy92@gmail.com', 'fa5ae93dd34a3ef44f2b8051fdb0eb130f1cfcfa', 'member', '', 0, 0),
(13, 'Ivy', 'ngivy0912@gmail.com', 'fa5ae93dd34a3ef44f2b8051fdb0eb130f1cfcfa', 'member', '', 1, 1),
(14, 'huyxing', 'xinghuy70@gmail.com', 'fa5ae93dd34a3ef44f2b8051fdb0eb130f1cfcfa', 'member', '', 1, 1),
(15, 'Ivy', 'huyxing47@gmail.com', 'fa5ae93dd34a3ef44f2b8051fdb0eb130f1cfcfa', 'member', '', 0, 0),
(16, 'Ivy', 'ngoswlad73@gmail.com', 'fa5ae93dd34a3ef44f2b8051fdb0eb130f1cfcfa', 'member', '', 0, 0),
(17, 'Ivy', 'fuquangong@gmail.com', 'fa5ae93dd34a3ef44f2b8051fdb0eb130f1cfcfa', 'member', '6affb8e5c10ba.jpg', 1, 1);

-- --------------------------------------------------------

--
-- 表的结构 `wishlist`
--

CREATE TABLE `wishlist` (
  `wishlist_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `added_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转储表的索引
--

--
-- 表的索引 `book`
--
ALTER TABLE `book`
  ADD PRIMARY KEY (`book_id`),
  ADD KEY `category_id` (`category_id`);

--
-- 表的索引 `borrowing`
--
ALTER TABLE `borrowing`
  ADD PRIMARY KEY (`borrowing_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- 表的索引 `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_id`);

--
-- 表的索引 `conversation`
--
ALTER TABLE `conversation`
  ADD PRIMARY KEY (`conversation_id`),
  ADD KEY `fk_conversation_user_id` (`user_id`);

--
-- 表的索引 `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- 表的索引 `fine`
--
ALTER TABLE `fine`
  ADD PRIMARY KEY (`fine_id`),
  ADD KEY `borrowing_id` (`borrowing_id`);

--
-- 表的索引 `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `fk_message_conversation_id` (`conversation_id`);

--
-- 表的索引 `reservation`
--
ALTER TABLE `reservation`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- 表的索引 `token`
--
ALTER TABLE `token`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- 表的索引 `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`);

--
-- 表的索引 `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `book`
--
ALTER TABLE `book`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `borrowing`
--
ALTER TABLE `borrowing`
  MODIFY `borrowing_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `category`
--
ALTER TABLE `category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `conversation`
--
ALTER TABLE `conversation`
  MODIFY `conversation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- 使用表AUTO_INCREMENT `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `fine`
--
ALTER TABLE `fine`
  MODIFY `fine_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `message`
--
ALTER TABLE `message`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- 使用表AUTO_INCREMENT `reservation`
--
ALTER TABLE `reservation`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `token`
--
ALTER TABLE `token`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用表AUTO_INCREMENT `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- 使用表AUTO_INCREMENT `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `wishlist_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 限制导出的表
--

--
-- 限制表 `book`
--
ALTER TABLE `book`
  ADD CONSTRAINT `category_id` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON UPDATE CASCADE;

--
-- 限制表 `borrowing`
--
ALTER TABLE `borrowing`
  ADD CONSTRAINT `fk_borrowing_book_id` FOREIGN KEY (`book_id`) REFERENCES `book` (`book_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_borrowing_user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE;

--
-- 限制表 `conversation`
--
ALTER TABLE `conversation`
  ADD CONSTRAINT `fk_conversation_user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE;

--
-- 限制表 `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `fk_feedback_book_id` FOREIGN KEY (`book_id`) REFERENCES `book` (`book_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_feedback_user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE;

--
-- 限制表 `fine`
--
ALTER TABLE `fine`
  ADD CONSTRAINT `fk_fine_borrowing_id` FOREIGN KEY (`borrowing_id`) REFERENCES `borrowing` (`borrowing_id`) ON UPDATE CASCADE;

--
-- 限制表 `message`
--
ALTER TABLE `message`
  ADD CONSTRAINT `fk_message_conversation_id` FOREIGN KEY (`conversation_id`) REFERENCES `conversation` (`conversation_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `reservation`
--
ALTER TABLE `reservation`
  ADD CONSTRAINT `fk_reservation_book_id` FOREIGN KEY (`book_id`) REFERENCES `book` (`book_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reservation_user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE;

--
-- 限制表 `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `fk_wishlist_book_id` FOREIGN KEY (`book_id`) REFERENCES `book` (`book_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_wishlist_user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
